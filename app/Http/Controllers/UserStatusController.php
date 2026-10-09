<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserStatus;
use App\Models\UserStatusResponse;
use App\Models\UserStatusView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserStatusController extends Controller
{
    /**
     * Status aktif dikelompokkan per user: status sendiri dulu, lalu yang
     * punya status belum dilihat, lalu sisanya — terbaru di atas.
     */
    public function index(Request $request): JsonResponse
    {
        $me = $request->user()->id;
        $statuses = UserStatus::active()
            ->with('user:id,name,avatar_updated_at')
            ->withCount('views')
            ->withExists(['views as seen' => fn ($query) => $query->where('user_id', $me)])
            ->with(['responses' => fn ($query) => $query->select('id', 'user_status_id', 'user_id', 'type', 'emoji', 'read_at')])
            ->oldest()
            ->get();

        $mentionNames = User::whereIn('id', $statuses->pluck('mentions')->flatten()->filter()->unique())->pluck('name', 'id');

        $groups = $statuses->groupBy('user_id')->map(function ($items) use ($me, $mentionNames) {
            $user = $items->first()->user;
            $mentionsMe = fn (UserStatus $status) => in_array($me, $status->mentions ?? [], true);

            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'initials' => self::initials($user->name),
                'avatar' => $user->avatarUrl(),
                'mine' => $user->id === $me,
                'unseen' => $user->id !== $me && $items->contains(fn ($status) => ! $status->seen),
                // Ada status yang menyebut saya dan belum saya lihat.
                'mentions_me' => $user->id !== $me && $items->contains(fn ($status) => ! $status->seen && $mentionsMe($status)),
                'latest_at' => $items->max('created_at')->toIso8601String(),
                'new_responses' => $user->id === $me ? $items->sum(fn ($status) => $status->responses->whereNull('read_at')->count()) : 0,
                'statuses' => $items->map(fn (UserStatus $status) => [
                    'id' => $status->id,
                    'body' => $status->body,
                    'background' => $status->background,
                    'time' => $status->created_at->locale('id')->diffForHumans(),
                    'seen' => (bool) $status->seen,
                    'mentions' => collect($status->mentions ?? [])->map(fn ($id) => $mentionNames[$id] ?? null)->filter()->values(),
                    'mentions_me' => $mentionsMe($status),
                    'views_count' => $user->id === $me ? $status->views_count : null,
                    // Reaksi saya sendiri (untuk status orang lain).
                    'my_reaction' => $status->responses->first(fn ($r) => $r->user_id === $me && $r->type === UserStatusResponse::TYPE_REACTION)?->emoji,
                    // Untuk status saya: ringkasan reaksi & jumlah tanggapan baru.
                    'reactions' => $user->id === $me
                        ? $status->responses->where('type', UserStatusResponse::TYPE_REACTION)->countBy('emoji')
                        : null,
                    'replies_count' => $user->id === $me ? $status->responses->where('type', UserStatusResponse::TYPE_REPLY)->count() : null,
                    'new_responses' => $user->id === $me ? $status->responses->whereNull('read_at')->count() : 0,
                ])->values(),
            ];
        })->sortBy([
            fn ($a, $b) => $b['mine'] <=> $a['mine'],
            fn ($a, $b) => $b['mentions_me'] <=> $a['mentions_me'],
            fn ($a, $b) => $b['unseen'] <=> $a['unseen'],
            fn ($a, $b) => strcmp($b['latest_at'], $a['latest_at']),
        ])->values();

        return response()->json([
            'groups' => $groups,
            'unseen_count' => $groups->where('unseen', true)->count(),
            'mention_count' => $groups->where('mentions_me', true)->count(),
            'response_count' => (int) $groups->sum('new_responses'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:500'],
            'background' => ['required', Rule::in(UserStatus::BACKGROUNDS)],
            'mentions' => ['nullable', 'array', 'max:20'],
            'mentions.*' => ['integer', 'exists:users,id'],
        ], [
            'body.required' => 'Tulis status dulu.',
            'body.max' => 'Status maksimal 500 karakter.',
        ]);

        // Simpan hanya mention yang namanya masih tertulis "@Nama" di teks.
        $body = trim($data['body']);
        $mentions = User::whereIn('id', $data['mentions'] ?? [])->get(['id', 'name'])
            ->filter(fn (User $user) => $user->id !== $request->user()->id && str_contains($body, '@'.$user->name))
            ->pluck('id')->values()->all();

        UserStatus::create([
            'user_id' => $request->user()->id,
            'body' => $body,
            'mentions' => $mentions ?: null,
            'background' => $data['background'],
            'expires_at' => now()->addHours(UserStatus::LIFETIME_HOURS),
        ]);

        return response()->json(['ok' => true], 201);
    }

    /** Daftar user untuk saran @mention. */
    public function users(Request $request): JsonResponse
    {
        return response()->json(User::where('id', '!=', $request->user()->id)
            ->orderBy('name')->get(['id', 'name', 'avatar_updated_at'])
            ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name, 'initials' => self::initials($user->name), 'avatar' => $user->avatarUrl()]));
    }

    public function destroy(Request $request, UserStatus $status): JsonResponse
    {
        abort_unless($status->user_id === $request->user()->id, 403);
        $status->delete();

        return response()->json(['ok' => true]);
    }

    public function view(Request $request, UserStatus $status): JsonResponse
    {
        abort_unless($status->expires_at->isFuture(), 404);
        if ($status->user_id !== $request->user()->id) {
            UserStatusView::firstOrCreate(
                ['user_status_id' => $status->id, 'user_id' => $request->user()->id],
                ['viewed_at' => now()],
            );
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Yang sudah melihat (beserta reaksinya) dan balasan — hanya untuk pemilik
     * status. Membuka daftar ini menandai semua tanggapan sebagai sudah dibaca.
     */
    public function viewers(Request $request, UserStatus $status): JsonResponse
    {
        abort_unless($status->user_id === $request->user()->id, 403);

        $responses = $status->responses()->with('user:id,name,avatar_updated_at')->latest()->get();
        $reactions = $responses->where('type', UserStatusResponse::TYPE_REACTION)->keyBy('user_id');

        $viewers = $status->views()->with('user:id,name,avatar_updated_at')->latest('viewed_at')->get()
            ->map(fn (UserStatusView $view) => [
                'name' => $view->user?->name ?? '-',
                'initials' => self::initials($view->user?->name ?? '-'),
                'avatar' => $view->user?->avatarUrl(),
                'time' => $view->viewed_at->locale('id')->diffForHumans(),
                'reaction' => $reactions->get($view->user_id)?->emoji,
            ]);

        $replies = $responses->where('type', UserStatusResponse::TYPE_REPLY)->values()
            ->map(fn (UserStatusResponse $reply) => [
                'name' => $reply->user?->name ?? '-',
                'initials' => self::initials($reply->user?->name ?? '-'),
                'avatar' => $reply->user?->avatarUrl(),
                'body' => $reply->body,
                'time' => $reply->created_at->locale('id')->diffForHumans(),
                'new' => $reply->read_at === null,
            ]);

        $status->responses()->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['viewers' => $viewers, 'replies' => $replies]);
    }

    /** Beri, ganti, atau batalkan reaksi (emoji kosong = batal). */
    public function react(Request $request, UserStatus $status): JsonResponse
    {
        $this->ensureCanRespond($request, $status);
        $data = $request->validate(['emoji' => ['nullable', Rule::in(UserStatusResponse::EMOJIS)]]);
        $existing = $status->responses()->where('user_id', $request->user()->id)
            ->where('type', UserStatusResponse::TYPE_REACTION)->first();

        if (empty($data['emoji'])) {
            $existing?->delete();
        } elseif ($existing) {
            if ($existing->emoji !== $data['emoji']) {
                $existing->update(['emoji' => $data['emoji'], 'read_at' => null]);
            }
        } else {
            $status->responses()->create([
                'user_id' => $request->user()->id,
                'type' => UserStatusResponse::TYPE_REACTION,
                'emoji' => $data['emoji'],
            ]);
        }

        return response()->json(['emoji' => $data['emoji'] ?? null]);
    }

    public function reply(Request $request, UserStatus $status): JsonResponse
    {
        $this->ensureCanRespond($request, $status);
        $data = $request->validate(['body' => ['required', 'string', 'max:300']], [
            'body.required' => 'Tulis balasan dulu.',
            'body.max' => 'Balasan maksimal 300 karakter.',
        ]);

        $status->responses()->create([
            'user_id' => $request->user()->id,
            'type' => UserStatusResponse::TYPE_REPLY,
            'body' => trim($data['body']),
        ]);

        return response()->json(['ok' => true], 201);
    }

    private function ensureCanRespond(Request $request, UserStatus $status): void
    {
        abort_unless($status->expires_at->isFuture(), 404);
        abort_if($status->user_id === $request->user()->id, 403, 'Tidak bisa menanggapi status sendiri.');
    }

    private static function initials(string $name): string
    {
        return mb_strtoupper(collect(preg_split('/\s+/', trim($name)))->filter()->map(fn ($word) => mb_substr($word, 0, 1))->take(2)->implode(''));
    }
}
