<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserStatus;
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
            ->with('user:id,name')
            ->withCount('views')
            ->withExists(['views as seen' => fn ($query) => $query->where('user_id', $me)])
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
                'mine' => $user->id === $me,
                'unseen' => $user->id !== $me && $items->contains(fn ($status) => ! $status->seen),
                // Ada status yang menyebut saya dan belum saya lihat.
                'mentions_me' => $user->id !== $me && $items->contains(fn ($status) => ! $status->seen && $mentionsMe($status)),
                'latest_at' => $items->max('created_at')->toIso8601String(),
                'statuses' => $items->map(fn (UserStatus $status) => [
                    'id' => $status->id,
                    'body' => $status->body,
                    'background' => $status->background,
                    'time' => $status->created_at->locale('id')->diffForHumans(),
                    'seen' => (bool) $status->seen,
                    'mentions' => collect($status->mentions ?? [])->map(fn ($id) => $mentionNames[$id] ?? null)->filter()->values(),
                    'mentions_me' => $mentionsMe($status),
                    'views_count' => $user->id === $me ? $status->views_count : null,
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
            ->orderBy('name')->get(['id', 'name'])
            ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name, 'initials' => self::initials($user->name)]));
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

    /** Daftar yang sudah melihat — hanya untuk pemilik status. */
    public function viewers(Request $request, UserStatus $status): JsonResponse
    {
        abort_unless($status->user_id === $request->user()->id, 403);

        return response()->json($status->views()->with('user:id,name')->latest('viewed_at')->get()
            ->map(fn (UserStatusView $view) => [
                'name' => $view->user?->name ?? '-',
                'initials' => self::initials($view->user?->name ?? '-'),
                'time' => $view->viewed_at->locale('id')->diffForHumans(),
            ]));
    }

    private static function initials(string $name): string
    {
        return mb_strtoupper(collect(preg_split('/\s+/', trim($name)))->filter()->map(fn ($word) => mb_substr($word, 0, 1))->take(2)->implode(''));
    }
}
