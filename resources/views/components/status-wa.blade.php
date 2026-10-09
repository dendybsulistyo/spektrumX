{{--
    Status ala WhatsApp (teks, 24 jam, semua user bisa melihat).
    Dipasang sekali di navbar (layouts/app.blade.php).
--}}
@php($backgrounds = \App\Models\UserStatus::BACKGROUNDS)

<div x-data="statusWa({
        indexUrl: @js(route('status.index')),
        storeUrl: @js(route('status.store')),
        usersUrl: @js(route('status.users')),
        baseUrl: @js(url('/status')),
        backgrounds: @js($backgrounds),
     })"
     x-init="init()"
     @keydown.escape.window="closeAll()"
     class="relative">

    {{-- Tombol di navbar --}}
    <button type="button" @click="togglePanel()" class="sw-trigger" :class="unseenCount > 0 && 'has-new'" title="Status">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-[18px] w-[18px]">
            <circle cx="12" cy="12" r="9" stroke-dasharray="3.2 2.4"/>
            <circle cx="12" cy="12" r="4.2"/>
        </svg>
        <b x-show="unseenCount > 0" x-cloak x-text="mentionCount > 0 ? '@' : unseenCount" :class="mentionCount > 0 && 'is-mention'"></b>
    </button>

    {{-- Panel daftar status --}}
    <div x-show="panelOpen" x-cloak @click.outside="panelOpen = false" class="sw-panel">
        <div class="sw-panel-head">
            <strong>Status</strong>
            <span>hilang otomatis setelah 24 jam</span>
        </div>

        <button type="button" class="sw-row" @click="mine ? openViewer(mine) : openComposer()">
            <span class="sw-avatar" :class="mine ? 'ring-mine' : 'ring-none'">
                <span x-text="myInitials"></span>
                <i class="sw-plus" @click.stop="openComposer()" title="Tambah status">+</i>
            </span>
            <span class="sw-row-text">
                <strong>Status saya</strong>
                <small x-text="mine ? mine.statuses.length + ' status · ' + mine.statuses[mine.statuses.length - 1].time : 'Ketuk untuk menulis status'"></small>
            </span>
        </button>

        <template x-if="others.filter(g => g.unseen).length">
            <div class="sw-label">Terbaru</div>
        </template>
        <template x-for="group in others.filter(g => g.unseen)" :key="'u' + group.user_id">
            <button type="button" class="sw-row" @click="openViewer(group)">
                <span class="sw-avatar ring-new"><span x-text="group.initials"></span></span>
                <span class="sw-row-text">
                    <strong x-text="group.name"></strong>
                    <small x-text="group.statuses[group.statuses.length - 1].time"></small>
                </span>
                <em class="sw-mention-tag" x-show="group.mentions_me">@ menyebut Anda</em>
            </button>
        </template>

        <template x-if="others.filter(g => !g.unseen).length">
            <div class="sw-label">Sudah dilihat</div>
        </template>
        <template x-for="group in others.filter(g => !g.unseen)" :key="'s' + group.user_id">
            <button type="button" class="sw-row" @click="openViewer(group)">
                <span class="sw-avatar ring-seen"><span x-text="group.initials"></span></span>
                <span class="sw-row-text">
                    <strong x-text="group.name"></strong>
                    <small x-text="group.statuses[group.statuses.length - 1].time"></small>
                </span>
            </button>
        </template>

        <template x-if="!others.length">
            <div class="sw-empty">Belum ada status dari rekan kerja.</div>
        </template>
    </div>

    <template x-teleport="body">
        <div>
            {{-- Penulis status --}}
            <div x-show="composerOpen" x-cloak class="sw-full" :style="`background:${draft.background}`">
                <div class="sw-full-top">
                    <button type="button" class="sw-icon-btn" @click="composerOpen = false" title="Batal">&times;</button>
                    <div class="sw-swatches">
                        <template x-for="color in backgrounds" :key="color">
                            <button type="button" class="sw-swatch" :style="`background:${color}`"
                                    :class="draft.background === color && 'is-active'" @click="draft.background = color"></button>
                        </template>
                    </div>
                </div>
                <div class="sw-composer-wrap">
                <div class="sw-composer-box">
                <textarea x-ref="composer" x-model="draft.body" maxlength="500" rows="2" class="sw-composer"
                          @input="detectMention()" @click="detectMention()"
                          @keydown.arrow-down="mentionMove($event, 1)" @keydown.arrow-up="mentionMove($event, -1)"
                          @keydown.enter="mentionPick($event)" @keydown.tab="mentionPick($event)"
                          placeholder="Ketik status… (ketik @ untuk menyebut rekan)" @keydown.ctrl.enter="submit()" @keydown.meta.enter="submit()"></textarea>
                <div class="sw-suggest" x-show="mention.open && mentionResults().length" x-cloak>
                    <template x-for="(user, i) in mentionResults()" :key="user.id">
                        <button type="button" class="sw-row" :class="i === mention.index && 'is-active'" @mousedown.prevent="insertMention(user)">
                            <span class="sw-avatar ring-none" style="width:30px;height:30px;"><span x-text="user.initials"></span></span>
                            <span class="sw-row-text"><strong x-text="user.name"></strong></span>
                        </button>
                    </template>
                </div>
                </div>
                </div>
                <div class="sw-full-bottom">
                    <span class="sw-counter" x-text="draft.body.length + '/500'"></span>
                    <span class="sw-error" x-show="error" x-text="error"></span>
                    <button type="button" class="sw-send" @click="submit()" :disabled="sending || !draft.body.trim()">
                        <span x-text="sending ? 'Mengirim…' : 'Kirim status'"></span>
                    </button>
                </div>
            </div>

            {{-- Penampil status --}}
            <div x-show="viewer.open" x-cloak class="sw-full" :style="`background:${current()?.background ?? '#1b2236'}`"
                 @keydown.arrow-right.window="viewer.open && next()" @keydown.arrow-left.window="viewer.open && prev()">
                <div class="sw-progress">
                    <template x-for="(status, i) in (viewer.group?.statuses ?? [])" :key="status.id">
                        <span><i :style="`width:${i < viewer.index ? 100 : (i === viewer.index ? viewer.progress : 0)}%`"></i></span>
                    </template>
                </div>
                <div class="sw-full-top">
                    <div class="sw-who">
                        <span class="sw-avatar ring-none" style="width:36px;height:36px;"><span x-text="viewer.group?.initials"></span></span>
                        <div>
                            <strong x-text="viewer.group?.mine ? 'Status saya' : viewer.group?.name"></strong>
                            <small x-text="current()?.time"></small>
                        </div>
                        <span class="sw-chip" x-show="current()?.mentions_me">@ Anda disebut</span>
                    </div>
                    <div style="display:flex; gap:6px;">
                        <template x-if="viewer.group?.mine">
                            <button type="button" class="sw-icon-btn" @click="removeCurrent()" title="Hapus status ini">
                                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" style="width:18px;height:18px"><path d="M4 6h12M8 6V4h4v2M6 6l.7 10h6.6L14 6"/></svg>
                            </button>
                        </template>
                        <button type="button" class="sw-icon-btn" @click="closeViewer()" title="Tutup">&times;</button>
                    </div>
                </div>

                <div class="sw-stage" @mousedown="pause()" @mouseup="resume()" @mouseleave="resume()"
                     @touchstart.passive="pause()" @touchend="resume()">
                    <button type="button" class="sw-tap sw-tap-left" @click="prev()" aria-label="Sebelumnya"></button>
                    <p class="sw-body" x-html="renderBody(current())"></p>
                    <button type="button" class="sw-tap sw-tap-right" @click="next()" aria-label="Berikutnya"></button>
                </div>

                <div class="sw-full-bottom" style="justify-content:center;">
                    <template x-if="viewer.group?.mine">
                        <button type="button" class="sw-seen-btn" @click="toggleViewers()">
                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" style="width:16px;height:16px"><path d="M1.5 10S4.5 4.5 10 4.5 18.5 10 18.5 10 15.5 15.5 10 15.5 1.5 10 1.5 10z"/><circle cx="10" cy="10" r="2.5"/></svg>
                            Dilihat <b x-text="current()?.views_count ?? 0"></b>
                        </button>
                    </template>
                </div>

                <div x-show="viewers.open" x-cloak class="sw-viewers" @click.outside="viewers.open = false; resume()">
                    <div class="sw-viewers-head">Dilihat oleh <b x-text="viewers.list.length"></b></div>
                    <template x-if="viewers.loading"><div class="sw-empty">Memuat…</div></template>
                    <template x-if="!viewers.loading && !viewers.list.length"><div class="sw-empty">Belum ada yang melihat.</div></template>
                    <template x-for="(v, i) in viewers.list" :key="i">
                        <div class="sw-row" style="cursor:default;">
                            <span class="sw-avatar ring-none" style="width:34px;height:34px;"><span x-text="v.initials"></span></span>
                            <span class="sw-row-text"><strong x-text="v.name"></strong><small x-text="v.time"></small></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </template>
</div>

@once
        <style>
            .sw-trigger { position: relative; display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; padding: 0; margin-right: 10px; font-size: 13px; font-weight: 600; color: #5d5a52; background: transparent; border: 1px solid #e3ddcf; border-radius: 999px; cursor: pointer; }
            .sw-trigger:hover { color: #1b2236; background: #f4efe3; }
            .sw-trigger.has-new { color: #1b2236; border-color: transparent; background: linear-gradient(#fffdf8, #fffdf8) padding-box, conic-gradient(#0f8fb3, #c8246c, #f2c200, #1b2236, #0f8fb3) border-box; border: 2px solid transparent; }
            .sw-trigger b.is-mention { background: #0f8fb3; }
            .sw-trigger b { position: absolute; top: -5px; right: -6px; min-width: 18px; border: 2px solid #fffdf8; box-sizing: content-box; height: 18px; padding: 0 5px; font-size: 11px; line-height: 18px; text-align: center; color: #fff; background: #c8246c; border-radius: 999px; }
            .sw-panel { position: absolute; right: 10px; top: calc(100% + 8px); z-index: 60; width: 320px; max-height: 70vh; overflow-y: auto; padding: 6px; background: #fffdf8; border: 1px solid #e3ddcf; border-radius: 12px; box-shadow: 0 18px 40px -12px rgba(27, 34, 54, .35); }
            .sw-panel-head { display: flex; align-items: baseline; justify-content: space-between; padding: 8px 10px 10px; border-bottom: 1px dashed #cfc7b5; margin-bottom: 4px; }
            .sw-panel-head strong { font-size: 15px; color: #1b2236; }
            .sw-panel-head span { font-size: 11px; color: #77736a; }
            .sw-label { padding: 10px 10px 4px; font-size: 10.5px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: #77736a; }
            .sw-row { display: flex; width: 100%; align-items: center; gap: 12px; padding: 7px 10px; text-align: left; background: transparent; border: 0; border-radius: 8px; cursor: pointer; }
            .sw-row:hover { background: #f4efe3; }
            .sw-row-text { display: flex; min-width: 0; flex-direction: column; }
            .sw-row-text strong { font-size: 13.5px; color: #1b2236; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .sw-row-text small { font-size: 11.5px; color: #77736a; }
            .sw-avatar { position: relative; flex: 0 0 auto; display: inline-flex; align-items: center; justify-content: center; width: 42px; height: 42px; border-radius: 50%; padding: 2.5px; }
            .sw-avatar > span { display: flex; width: 100%; height: 100%; align-items: center; justify-content: center; border-radius: 50%; background: #1b2236; color: #fff; font-size: 12.5px; font-weight: 700; border: 2px solid #fffdf8; }
            .sw-avatar.ring-new { background: conic-gradient(#0f8fb3, #c8246c, #f2c200, #2e8b57, #0f8fb3); }
            .sw-avatar.ring-mine { background: #0f8fb3; }
            .sw-avatar.ring-seen { background: #cfc7b5; }
            .sw-avatar.ring-seen > span { background: #77736a; }
            .sw-avatar.ring-none { padding: 0; }
            .sw-avatar.ring-none > span { border: 0; }
            .sw-plus { position: absolute; right: -2px; bottom: -2px; display: flex; width: 18px; height: 18px; align-items: center; justify-content: center; font-style: normal; font-size: 14px; font-weight: 700; line-height: 1; color: #fff; background: #0f8fb3; border: 2px solid #fffdf8; border-radius: 50%; }
            .sw-mention-tag { margin-left: auto; padding: 2px 7px; font-size: 10.5px; font-style: normal; font-weight: 700; color: #0b6a86; background: #e2f1f7; border-radius: 999px; white-space: nowrap; }
            .sw-composer-box { position: relative; width: min(760px, 92vw); }
            .sw-suggest { position: absolute; left: 50%; top: calc(100% + 10px); z-index: 2; width: min(320px, 90vw); max-height: 260px; overflow-y: auto; padding: 5px; background: #fffdf8; border-radius: 12px; transform: translateX(-50%); box-shadow: 0 18px 40px -10px rgba(0, 0, 0, .45); }
            .sw-suggest .sw-row.is-active { background: #f1ece0; }
            .sw-mention { padding: 0 .18em; border-radius: .2em; background: rgba(255, 255, 255, .22); box-shadow: inset 0 -.12em 0 rgba(255, 255, 255, .8); }
            .sw-chip { padding: 3px 9px; font-size: 11.5px; font-weight: 700; color: #1b2236; background: #fff; border-radius: 999px; }
            .sw-empty { padding: 14px 10px; font-size: 12.5px; color: #77736a; text-align: center; }

            .sw-full { position: fixed; inset: 0; z-index: 9999; display: flex; flex-direction: column; color: #fff; font-family: 'IBM Plex Sans', system-ui, sans-serif; transition: background .25s ease; }
            .sw-full-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 20px; }
            .sw-full-bottom { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 20px 22px; }
            .sw-icon-btn { display: inline-flex; width: 38px; height: 38px; align-items: center; justify-content: center; font-size: 26px; line-height: 1; color: #fff; background: rgba(0, 0, 0, .18); border: 0; border-radius: 50%; cursor: pointer; }
            .sw-icon-btn:hover { background: rgba(0, 0, 0, .32); }
            .sw-swatches { display: flex; gap: 8px; }
            .sw-swatch { width: 26px; height: 26px; border-radius: 50%; border: 2px solid rgba(255, 255, 255, .55); cursor: pointer; }
            .sw-swatch.is-active { border-color: #fff; box-shadow: 0 0 0 3px rgba(255, 255, 255, .35); }
            .sw-composer-wrap { flex: 1; display: flex; align-items: center; justify-content: center; }
            .sw-composer { field-sizing: content; min-height: 1.3em; max-height: 70vh; width: min(760px, 92vw); margin: 0 auto; padding: 0; font-size: clamp(24px, 3.4vw, 40px); font-weight: 600; line-height: 1.3; text-align: center; color: #fff; background: transparent !important; border: 0 !important; outline: none; resize: none; box-shadow: none !important; }
            .sw-composer::placeholder { color: rgba(255, 255, 255, .55); }
            .sw-counter { font-family: 'IBM Plex Mono', monospace; font-size: 12px; opacity: .75; }
            .sw-error { font-size: 13px; background: rgba(0, 0, 0, .25); padding: 4px 10px; border-radius: 6px; }
            .sw-send { height: 42px; padding: 0 22px; font-size: 14px; font-weight: 700; color: #1b2236; background: #fff; border: 0; border-radius: 999px; cursor: pointer; }
            .sw-send:disabled { opacity: .5; cursor: not-allowed; }
            .sw-progress { display: flex; gap: 4px; padding: 10px 20px 0; }
            .sw-progress span { flex: 1; height: 3px; overflow: hidden; background: rgba(255, 255, 255, .35); border-radius: 2px; }
            .sw-progress i { display: block; height: 100%; background: #fff; }
            .sw-who { display: flex; align-items: center; gap: 10px; }
            .sw-who strong { display: block; font-size: 14.5px; }
            .sw-who small { font-size: 12px; opacity: .8; }
            .sw-stage { position: relative; flex: 1; display: flex; align-items: center; justify-content: center; padding: 0 8vw; user-select: none; }
            .sw-body { max-width: 820px; margin: 0; font-size: clamp(22px, 3.2vw, 40px); font-weight: 600; line-height: 1.35; text-align: center; white-space: pre-wrap; word-break: break-word; }
            .sw-tap { position: absolute; top: 0; bottom: 0; width: 30%; background: transparent; border: 0; cursor: pointer; }
            .sw-tap-left { left: 0; }
            .sw-tap-right { right: 0; }
            .sw-seen-btn { display: inline-flex; align-items: center; gap: 6px; height: 36px; padding: 0 16px; font-size: 13px; font-weight: 600; color: #fff; background: rgba(0, 0, 0, .22); border: 0; border-radius: 999px; cursor: pointer; }
            .sw-viewers { position: absolute; left: 50%; bottom: 80px; width: min(360px, 92vw); max-height: 50vh; overflow-y: auto; padding: 6px; color: #262a33; background: #fffdf8; border-radius: 12px; transform: translateX(-50%); box-shadow: 0 18px 40px -10px rgba(0, 0, 0, .45); }
            .sw-viewers-head { padding: 8px 10px; font-size: 13px; font-weight: 600; color: #1b2236; border-bottom: 1px dashed #cfc7b5; }
        </style>
        <script>
            function statusWa(config) {
                return {
                    ...config,
                    groups: [],
                    unseenCount: 0,
                    mentionCount: 0,
                    users: null,
                    mention: { open: false, query: '', start: 0, index: 0 },
                    myInitials: @js(mb_strtoupper(collect(preg_split('/\s+/', trim(auth()->user()->name)))->filter()->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode(''))),
                    panelOpen: false,
                    composerOpen: false,
                    sending: false,
                    error: '',
                    draft: { body: '', background: config.backgrounds[0], mentions: [] },
                    viewer: { open: false, group: null, index: 0, progress: 0, paused: false, timer: null },
                    viewers: { open: false, loading: false, list: [] },
                    duration: 6000,
                    get mine() { return this.groups.find(g => g.mine) || null; },
                    get others() { return this.groups.filter(g => !g.mine); },
                    init() {
                        this.load();
                        setInterval(() => { if (!document.hidden && !this.viewer.open) this.load(); }, 60000);
                    },
                    load() {
                        return axios.get(this.indexUrl).then(r => {
                            this.groups = r.data.groups;
                            this.unseenCount = r.data.unseen_count;
                            this.mentionCount = r.data.mention_count;
                        }).catch(() => {});
                    },
                    togglePanel() {
                        this.panelOpen = !this.panelOpen;
                        if (this.panelOpen) this.load();
                    },
                    closeAll() {
                        if (this.viewers.open) { this.viewers.open = false; this.resume(); return; }
                        if (this.viewer.open) { this.closeViewer(); return; }
                        this.composerOpen = false;
                        this.panelOpen = false;
                    },
                    openComposer() {
                        this.panelOpen = false;
                        this.error = '';
                        this.draft.body = '';
                        this.draft.mentions = [];
                        this.mention.open = false;
                        this.composerOpen = true;
                        if (this.users === null) {
                            this.users = [];
                            axios.get(this.usersUrl).then(r => { this.users = r.data; }).catch(() => { this.users = null; });
                        }
                        this.$nextTick(() => this.$refs.composer.focus());
                    },
                    submit() {
                        if (this.sending || !this.draft.body.trim()) return;
                        this.sending = true;
                        this.error = '';
                        axios.post(this.storeUrl, this.draft)
                            .then(() => { this.composerOpen = false; return this.load(); })
                            .catch(e => { this.error = e.response?.data?.message || 'Gagal mengirim status.'; })
                            .finally(() => { this.sending = false; });
                    },
                    detectMention() {
                        const el = this.$refs.composer;
                        const before = this.draft.body.slice(0, el.selectionStart);
                        const match = before.match(/(^|\s)@([^@\n]{0,30})$/);
                        if (!match) { this.mention.open = false; return; }
                        this.mention.query = match[2].toLowerCase();
                        this.mention.start = el.selectionStart - match[2].length - 1;
                        this.mention.index = 0;
                        this.mention.open = true;
                    },
                    mentionResults() {
                        const q = this.mention.query.trim();
                        return (this.users || [])
                            .filter(u => q === '' || u.name.toLowerCase().includes(q))
                            .sort((a, b) => a.name.toLowerCase().startsWith(q) === b.name.toLowerCase().startsWith(q) ? 0 : (a.name.toLowerCase().startsWith(q) ? -1 : 1))
                            .slice(0, 6);
                    },
                    mentionMove(event, step) {
                        if (!this.mention.open || !this.mentionResults().length) return;
                        event.preventDefault();
                        const total = this.mentionResults().length;
                        this.mention.index = (this.mention.index + step + total) % total;
                    },
                    mentionPick(event) {
                        if (!this.mention.open || event.ctrlKey || event.metaKey) return;
                        const user = this.mentionResults()[this.mention.index];
                        if (!user) return;
                        event.preventDefault();
                        this.insertMention(user);
                    },
                    insertMention(user) {
                        const el = this.$refs.composer;
                        const end = el.selectionStart;
                        const text = `@${user.name} `;
                        this.draft.body = (this.draft.body.slice(0, this.mention.start) + text + this.draft.body.slice(end)).slice(0, 500);
                        if (!this.draft.mentions.includes(user.id)) this.draft.mentions.push(user.id);
                        this.mention.open = false;
                        this.$nextTick(() => { const pos = this.mention.start + text.length; el.focus(); el.setSelectionRange(pos, pos); });
                    },
                    renderBody(status) {
                        if (!status) return '';
                        const escape = t => t.replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
                        let html = escape(status.body);
                        [...(status.mentions || [])].sort((a, b) => b.length - a.length).forEach(name => {
                            const safe = escape('@' + name);
                            html = html.split(safe).join(`<span class="sw-mention">${safe}</span>`);
                        });
                        return html;
                    },
                    current() {
                        return this.viewer.group?.statuses[this.viewer.index] ?? null;
                    },
                    openViewer(group) {
                        this.panelOpen = false;
                        const firstUnseen = group.mine ? 0 : group.statuses.findIndex(s => !s.seen);
                        this.viewer.group = group;
                        this.viewer.index = Math.max(0, firstUnseen);
                        this.viewer.open = true;
                        this.startStatus();
                    },
                    startStatus() {
                        clearInterval(this.viewer.timer);
                        this.viewer.progress = 0;
                        this.viewer.paused = false;
                        this.viewers.open = false;
                        const status = this.current();
                        if (!status) return this.closeViewer();
                        if (!this.viewer.group.mine && !status.seen) {
                            status.seen = true;
                            axios.post(`${this.baseUrl}/${status.id}/lihat`).catch(() => {});
                        }
                        const step = 50;
                        this.viewer.timer = setInterval(() => {
                            if (this.viewer.paused) return;
                            this.viewer.progress += step / this.duration * 100;
                            if (this.viewer.progress >= 100) this.next();
                        }, step);
                    },
                    next() {
                        if (this.viewer.index < this.viewer.group.statuses.length - 1) {
                            this.viewer.index++;
                            return this.startStatus();
                        }
                        const order = this.groups;
                        const at = order.findIndex(g => g.user_id === this.viewer.group.user_id);
                        const following = order.slice(at + 1).find(g => !g.mine && g.unseen);
                        following ? this.openViewer(following) : this.closeViewer();
                    },
                    prev() {
                        if (this.viewer.index > 0) { this.viewer.index--; this.startStatus(); }
                        else { this.viewer.progress = 0; }
                    },
                    pause() { this.viewer.paused = true; },
                    resume() { if (!this.viewers.open) this.viewer.paused = false; },
                    closeViewer() {
                        clearInterval(this.viewer.timer);
                        this.viewer.open = false;
                        this.viewers.open = false;
                        this.load();
                    },
                    toggleViewers() {
                        if (this.viewers.open) { this.viewers.open = false; return this.resume(); }
                        this.pause();
                        this.viewers.open = true;
                        this.viewers.loading = true;
                        this.viewers.list = [];
                        axios.get(`${this.baseUrl}/${this.current().id}/dilihat`)
                            .then(r => { this.viewers.list = r.data; })
                            .finally(() => { this.viewers.loading = false; });
                    },
                    removeCurrent() {
                        if (!confirm('Hapus status ini?')) return;
                        const status = this.current();
                        axios.delete(`${this.baseUrl}/${status.id}`).then(() => {
                            this.viewer.group.statuses.splice(this.viewer.index, 1);
                            if (!this.viewer.group.statuses.length) return this.closeViewer();
                            this.viewer.index = Math.min(this.viewer.index, this.viewer.group.statuses.length - 1);
                            this.startStatus();
                        });
                    },
                };
            }
        </script>
@endonce
