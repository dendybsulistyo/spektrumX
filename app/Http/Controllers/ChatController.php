<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(): JsonResponse
    {
        $me = auth()->id();

        $users = User::with('role')->where('id', '!=', $me)->get()->map(function (User $user) use ($me) {
            $last = ChatMessage::between($me, $user->id)->latest()->first();

            return [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role?->label,
                'initials' => $this->initials($user->name),
                'last_message' => $last?->body,
                'last_message_at' => $last?->created_at,
                'unread_count' => ChatMessage::where('sender_id', $user->id)
                    ->where('recipient_id', $me)
                    ->whereNull('read_at')
                    ->count(),
            ];
        });

        $sorted = $users->sortByDesc(fn ($u) => $u['last_message_at']?->timestamp ?? -1)->values();

        return response()->json($sorted);
    }

    public function unreadCount(): JsonResponse
    {
        $count = ChatMessage::where('recipient_id', auth()->id())->whereNull('read_at')->count();

        return response()->json(['count' => $count]);
    }

    public function show(User $user): JsonResponse
    {
        $me = auth()->id();

        $messages = ChatMessage::between($me, $user->id)->latest()->limit(100)->get()->reverse()->values();

        ChatMessage::where('sender_id', $user->id)
            ->where('recipient_id', $me)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'user' => ['id' => $user->id, 'name' => $user->name, 'initials' => $this->initials($user->name)],
            'messages' => $messages,
        ]);
    }

    public function store(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message = ChatMessage::create([
            'sender_id' => auth()->id(),
            'recipient_id' => $user->id,
            'body' => $data['body'],
        ]);

        return response()->json($message);
    }

    private function initials(string $name): string
    {
        return collect(explode(' ', $name))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
    }
}
