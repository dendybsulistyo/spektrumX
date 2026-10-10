#!/usr/bin/env bash
# Pasang Tailscale di server production agar bisa SSH dari luar kantor
# tanpa domain, tanpa IP publik, tanpa membuka port di router.
#
# Pakai (sekali saja, di server):
#   sudo bash deploy/tailscale-ssh.sh
#   sudo bash deploy/tailscale-ssh.sh nama-server     (opsional, bawaan: spektrum-server)
#
# Aman dijalankan ulang. Skrip ini TIDAK mengubah aplikasi, database,
# PHP-FPM, Nginx/Apache, firewall, maupun konfigurasi SSH yang sudah ada.
#
# Untuk mencabut lagi:
#   sudo tailscale logout && sudo apt-get remove -y tailscale
set -euo pipefail

HOSTNAME_TS="${1:-spektrum-server}"

if [[ $EUID -ne 0 ]]; then
    echo "Jalankan dengan sudo:  sudo bash $0" >&2
    exit 1
fi

echo "==> Cek SSH server di mesin ini"
if systemctl is-active --quiet ssh || systemctl is-active --quiet sshd; then
    echo "    SSH aktif."
else
    echo "    PERINGATAN: layanan SSH (ssh/sshd) tidak aktif. Tailscale tetap dipasang,"
    echo "    tetapi SSH dari luar baru bisa setelah: sudo apt-get install -y openssh-server"
fi

if command -v tailscale >/dev/null 2>&1; then
    echo "==> Tailscale sudah terpasang ($(tailscale version | head -1)), lewati instalasi"
else
    echo "==> Pasang Tailscale (repositori resmi tailscale.com)"
    curl -fsSL https://tailscale.com/install.sh | sh
fi

echo "==> Aktifkan layanan Tailscale (ikut menyala saat server restart)"
systemctl enable --now tailscaled

echo "==> Hubungkan server ke akun Tailscale"
echo "    Sebentar lagi muncul link 'https://login.tailscale.com/...'."
echo "    Buka link itu di laptop/HP, lalu login dengan akun Tailscale bang."
tailscale up --hostname="${HOSTNAME_TS}"

echo
echo "==> Selesai. Server terdaftar sebagai: ${HOSTNAME_TS}"
echo "    IP Tailscale : $(tailscale ip -4 | head -1)"
echo
echo "Langkah berikutnya (di laptop/HP bang):"
echo "  1. Pasang aplikasi Tailscale, login dengan akun yang SAMA."
echo "  2. SSH seperti biasa:  ssh $(logname 2>/dev/null || echo USER)@${HOSTNAME_TS}"
echo "  3. PENTING: di https://login.tailscale.com/admin/machines, buka menu '...' pada"
echo "     ${HOSTNAME_TS} lalu pilih 'Disable key expiry'. Tanpa ini, setelah ~180 hari"
echo "     server minta login ulang dan akses remote terputus."
