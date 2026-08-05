<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\Role;
use App\Models\User;

/**
 * Pings every user whose role has a given permission via the existing 1:1
 * chat system — there's no broadcast/group channel, so this fires one
 * direct message per approver, from whoever triggered the notification.
 * Used for anything that needs an "X is waiting for your approval" nudge
 * (diskon requests, pembatalan requests, ...).
 */
class ApproverNotificationService
{
    public function notify(string $permission, string $body): void
    {
        $approverRoleIds = Role::query()
            ->whereJsonContains('permissions', $permission)
            ->pluck('id');

        $approvers = User::query()
            ->whereIn('role_id', $approverRoleIds)
            ->where('id', '!=', auth()->id())
            ->get();

        foreach ($approvers as $approver) {
            ChatMessage::create([
                'sender_id' => auth()->id(),
                'recipient_id' => $approver->id,
                'body' => $body,
            ]);
        }
    }
}
