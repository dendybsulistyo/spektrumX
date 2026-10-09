<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Reaksi emoji atau balasan teks ke sebuah status. */
class UserStatusResponse extends Model
{
    public const TYPE_REACTION = 'reaksi';

    public const TYPE_REPLY = 'balasan';

    public const EMOJIS = ['👍', '❤️', '😂', '🙏', '😮', '👏'];

    protected $fillable = ['user_status_id', 'user_id', 'type', 'emoji', 'body', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(UserStatus::class, 'user_status_id');
    }
}
