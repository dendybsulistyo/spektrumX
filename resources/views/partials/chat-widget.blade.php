<div x-data="chatWidget()" x-init="init()">
    <!-- Floating button (draggable) -->
    <button @pointerdown="startDrag($event)"
            :style="`right:${pos.x}px; bottom:${pos.y}px;`"
            class="fixed z-50 w-14 h-14 rounded-full bg-indigo-600 text-white shadow-lg flex items-center justify-center hover:bg-indigo-700 touch-none select-none cursor-grab active:cursor-grabbing">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
        </svg>

        <span x-show="unreadTotal > 0" x-cloak x-text="unreadTotal"
              class="absolute -top-1 -right-1 min-w-[20px] h-5 px-1 rounded-full bg-red-600 text-white text-xs font-semibold flex items-center justify-center"></span>
    </button>

    <!-- Panel (outer div only toggles visibility; inner div only handles position —
         keeping x-show and :style on separate elements so Alpine's reactive style
         rewrite on drag never clobbers the display:none that x-show relies on) -->
    <div x-show="panelOpen" x-cloak>
    <div :style="`right:${pos.x}px; bottom:${pos.y + 64}px;`"
         class="fixed w-80 h-[28rem] bg-white rounded-lg shadow-xl border border-gray-200 flex flex-col z-50">

        <!-- List view -->
        <template x-if="view === 'list'">
            <div class="flex flex-col h-full">
                <div class="px-4 py-3 border-b border-gray-200 shrink-0">
                    <h3 class="font-semibold text-gray-900 text-sm">Chat</h3>
                </div>
                <div class="flex-1 overflow-y-auto divide-y">
                    <template x-for="user in users" :key="user.id">
                        <button @click="openConversation(user)" class="w-full text-left px-4 py-3 hover:bg-gray-50 flex items-start gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-semibold shrink-0" x-text="user.initials"></div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-medium text-gray-900 truncate" x-text="user.name"></p>
                                    <span x-show="user.unread_count > 0" x-text="user.unread_count"
                                          class="shrink-0 min-w-[18px] h-[18px] px-1 rounded-full bg-red-600 text-white text-[10px] font-semibold flex items-center justify-center"></span>
                                </div>
                                <p class="text-xs text-gray-400 truncate" x-text="user.role"></p>
                                <p class="text-xs text-gray-500 truncate mt-0.5" x-text="user.last_message ?? 'Belum ada pesan'"></p>
                            </div>
                        </button>
                    </template>
                    <p x-show="users.length === 0" class="text-center text-xs text-gray-400 py-8">Tidak ada user lain.</p>
                </div>
            </div>
        </template>

        <!-- Chat view -->
        <template x-if="view === 'chat'">
            <div class="flex flex-col h-full">
                <div class="px-4 py-3 border-b border-gray-200 flex items-center gap-2 shrink-0">
                    <button @click="backToList()" class="text-gray-400 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                        </svg>
                    </button>
                    <h3 class="font-semibold text-gray-900 text-sm" x-text="activeUser?.name"></h3>
                </div>

                <div class="flex-1 overflow-y-auto px-4 py-3 space-y-2" x-ref="messageList">
                    <template x-for="msg in messages" :key="msg.id">
                        <div :class="isMine(msg) ? 'flex justify-end' : 'flex justify-start'">
                            <div>
                                <div :class="isMine(msg) ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-800'"
                                     class="rounded-lg px-3 py-1.5 text-sm max-w-[200px] break-words">
                                    <span x-text="msg.body"></span>
                                </div>
                                <p :class="isMine(msg) ? 'text-right' : 'text-left'" class="text-[10px] text-gray-400 mt-0.5" x-text="formatTime(msg.created_at)"></p>
                            </div>
                        </div>
                    </template>
                    <p x-show="messages.length === 0" class="text-center text-xs text-gray-400 py-8">Belum ada pesan. Mulai percakapan.</p>
                </div>

                <div class="p-3 border-t border-gray-200 shrink-0 flex gap-2">
                    <textarea x-model="newMessage" @keydown.enter.prevent="send()" rows="1"
                              placeholder="Tulis pesan..."
                              class="flex-1 resize-none rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    <button @click="send()" class="shrink-0 px-3 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700">
                        Kirim
                    </button>
                </div>
            </div>
        </template>
    </div>
    </div>
</div>

<script>
    function chatWidget() {
        return {
            panelOpen: false,
            view: 'list',
            users: [],
            unreadTotal: 0,
            activeUser: null,
            messages: [],
            newMessage: '',
            authId: {{ auth()->id() }},
            pollUnread: null,
            pollList: null,
            pollConvo: null,

            pos: { x: 24, y: 24 },
            dragging: false,
            dragMoved: false,
            dragStart: { mouseX: 0, mouseY: 0, x: 0, y: 0 },

            init() {
                const saved = localStorage.getItem('chatWidgetPos');
                if (saved) {
                    try { this.pos = JSON.parse(saved); } catch (e) {}
                }
                this.fetchUnread();
                this.pollUnread = setInterval(() => this.fetchUnread(), 5000);
            },

            startDrag(e) {
                this.dragging = true;
                this.dragMoved = false;
                this.dragStart = { mouseX: e.clientX, mouseY: e.clientY, x: this.pos.x, y: this.pos.y };

                const onMove = (ev) => {
                    const dx = ev.clientX - this.dragStart.mouseX;
                    const dy = ev.clientY - this.dragStart.mouseY;
                    if (Math.abs(dx) > 4 || Math.abs(dy) > 4) this.dragMoved = true;

                    let newX = this.dragStart.x - dx;
                    let newY = this.dragStart.y - dy;
                    newX = Math.min(Math.max(newX, 8), window.innerWidth - 64);
                    newY = Math.min(Math.max(newY, 8), window.innerHeight - 64);
                    this.pos = { x: newX, y: newY };
                };

                const onUp = () => {
                    this.dragging = false;
                    window.removeEventListener('pointermove', onMove);
                    window.removeEventListener('pointerup', onUp);
                    localStorage.setItem('chatWidgetPos', JSON.stringify(this.pos));

                    // Decide open/close ourselves — don't rely on the browser's
                    // native 'click' event, since it fires after mouseup
                    // regardless of how far the pointer moved in between.
                    if (!this.dragMoved) {
                        this.togglePanel();
                    }
                };

                window.addEventListener('pointermove', onMove);
                window.addEventListener('pointerup', onUp);
            },

            togglePanel() {
                this.panelOpen = !this.panelOpen;
                if (this.panelOpen && this.view === 'list') {
                    this.fetchUsers();
                    this.startListPolling();
                }
            },

            fetchUnread() {
                fetch('{{ route('chat.unread-count') }}')
                    .then(r => r.json())
                    .then(data => { this.unreadTotal = data.count; });
            },

            fetchUsers() {
                fetch('{{ route('chat.index') }}')
                    .then(r => r.json())
                    .then(data => { this.users = data; });
            },

            startListPolling() {
                clearInterval(this.pollList);
                this.pollList = setInterval(() => {
                    if (this.panelOpen && this.view === 'list') this.fetchUsers();
                }, 5000);
            },

            openConversation(user) {
                this.activeUser = user;
                this.view = 'chat';
                this.messages = [];
                this.fetchMessages();
                clearInterval(this.pollConvo);
                this.pollConvo = setInterval(() => this.fetchMessages(), 3000);
                this.fetchUnread();
            },

            backToList() {
                this.view = 'list';
                clearInterval(this.pollConvo);
                this.fetchUsers();
            },

            fetchMessages() {
                if (!this.activeUser) return;
                fetch(`/chat/${this.activeUser.id}`)
                    .then(r => r.json())
                    .then(data => {
                        this.messages = data.messages;
                        this.$nextTick(() => this.scrollToBottom());
                    });
            },

            send() {
                const body = this.newMessage.trim();
                if (!body || !this.activeUser) return;
                this.newMessage = '';

                fetch(`/chat/${this.activeUser.id}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ body }),
                })
                    .then(r => r.json())
                    .then(msg => {
                        this.messages.push(msg);
                        this.$nextTick(() => this.scrollToBottom());
                    });
            },

            scrollToBottom() {
                const el = this.$refs.messageList;
                if (el) el.scrollTop = el.scrollHeight;
            },

            isMine(msg) {
                return msg.sender_id === this.authId;
            },

            formatTime(iso) {
                if (!iso) return '';
                const d = new Date(iso);
                return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            },
        };
    }
</script>
