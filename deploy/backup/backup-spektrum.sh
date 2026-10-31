#!/usr/bin/env bash
# Backup harian spektrumX: database + file aplikasi (storage/app, .env).
#
# Tujuan (tiap tujuan berdiri sendiri; satu gagal, yang lain tetap jalan):
#   1. Hardisk server   : /var/backups/spektrum            (simpan KEEP_LOCAL hari)
#   2. Flashdisk USB    : /mnt/usb-backup/spektrum          (simpan KEEP_USB hari, bila ter-mount)
#   3. Cloudflare R2    : remote rclone "$R2_REMOTE"        (simpan KEEP_R2 hari, bila sudah disetel)
#
# Dipasang di /usr/local/bin/backup-spektrum.sh (root, chmod 700), dijalankan
# systemd timer backup-spektrum.timer. Log: /var/log/backup-spektrum.log
set -uo pipefail
umask 077

DB_NAME="spektrumXX"
DB_CNF="/root/.my-backup.cnf"          # [client] user=backup password=...
APP_DIR="/var/www/spektrumX"
LOCAL_DIR="/var/backups/spektrum"
USB_MOUNT="/mnt/usb-backup"
USB_DIR="$USB_MOUNT/spektrum"
R2_REMOTE="r2crypt:"                   # rclone crypt di atas bucket R2 (kosongkan untuk mematikan)
KEEP_LOCAL=14
KEEP_USB=30
KEEP_R2=30
MIN_FREE_GB=10

STAMP="$(date +%Y-%m-%d_%H%M)"
WORK="$LOCAL_DIR/$STAMP"
LOG="/var/log/backup-spektrum.log"
STATUS=0

log() { echo "$(date '+%F %T') $*" | tee -a "$LOG"; }
fail() { log "GAGAL: $*"; STATUS=1; }

log "=== Mulai backup $STAMP ==="

# --- 0. Ruang disk ---------------------------------------------------------
mkdir -p "$LOCAL_DIR"
free_gb=$(df -BG --output=avail "$LOCAL_DIR" | tail -1 | tr -dc '0-9')
if [ "${free_gb:-0}" -lt "$MIN_FREE_GB" ]; then
    fail "ruang disk tinggal ${free_gb} GB (< ${MIN_FREE_GB} GB), backup dibatalkan"
    exit 1
fi
mkdir -p "$WORK"

# --- 1. Database (konsisten tanpa mengunci tabel InnoDB) -------------------
if mysqldump --defaults-extra-file="$DB_CNF" --single-transaction --quick \
        --routines --triggers --events --hex-blob --default-character-set=utf8mb4 \
        "$DB_NAME" | gzip -6 > "$WORK/db-$DB_NAME.sql.gz" \
   && [ "${PIPESTATUS[0]}" -eq 0 ] && gzip -t "$WORK/db-$DB_NAME.sql.gz"; then
    log "DB ok ($(du -h "$WORK/db-$DB_NAME.sql.gz" | cut -f1))"
else
    fail "mysqldump $DB_NAME"
fi

# --- 2. File aplikasi: unggahan, tanda tangan, avatar, dan .env -------------
if tar -czf "$WORK/files.tar.gz" -C "$APP_DIR" storage/app .env 2>>"$LOG"; then
    log "File ok ($(du -h "$WORK/files.tar.gz" | cut -f1))"
else
    fail "tar storage/app + .env"
fi

( cd "$WORK" && sha256sum ./* > SHA256SUMS )

# --- 3. Rotasi hardisk server ----------------------------------------------
find "$LOCAL_DIR" -mindepth 1 -maxdepth 1 -type d -mtime +"$KEEP_LOCAL" -exec rm -rf {} + 2>>"$LOG"

# --- 4. Flashdisk USB ------------------------------------------------------
if mountpoint -q "$USB_MOUNT"; then
    mkdir -p "$USB_DIR"
    # FAT/exFAT: tanpa atribut pemilik/izin Linux.
    if cp -r "$WORK" "$USB_DIR/" && ( cd "$USB_DIR/$STAMP" && sha256sum -c --quiet SHA256SUMS ); then
        log "USB ok"
        find "$USB_DIR" -mindepth 1 -maxdepth 1 -type d -mtime +"$KEEP_USB" -exec rm -rf {} + 2>>"$LOG"
    else
        fail "salin ke USB"
    fi
    sync
else
    fail "flashdisk tidak ter-mount di $USB_MOUNT"
fi

# --- 5. Cloudflare R2 (terenkripsi oleh rclone crypt) ----------------------
if [ -n "$R2_REMOTE" ] && command -v rclone >/dev/null && rclone listremotes 2>/dev/null | grep -qx "${R2_REMOTE}"; then
    if rclone copy "$WORK" "${R2_REMOTE}spektrum/$STAMP" --log-file "$LOG" --log-level NOTICE; then
        log "R2 ok"
        rclone delete "${R2_REMOTE}spektrum" --min-age "${KEEP_R2}d" --log-file "$LOG" --log-level NOTICE
        rclone rmdirs "${R2_REMOTE}spektrum" --leave-root --log-file "$LOG" --log-level NOTICE
    else
        fail "unggah ke R2"
    fi
else
    log "R2 dilewati (remote ${R2_REMOTE:-'-'} belum disetel)"
fi

log "=== Selesai $STAMP (status $STATUS) ==="
exit "$STATUS"
