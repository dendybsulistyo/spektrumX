<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'avatar_svg',
        'avatar_photo',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'avatar_options' => 'array',
            'avatar_updated_at' => 'datetime',
            'is_active' => 'boolean',
            'deactivated_at' => 'datetime',
        ];
    }

    /**
     * Nonaktif hanya bila status benar-benar diset false. Nilai kosong (mis.
     * kolom belum dimigrasi) dianggap aktif agar tidak mengunci semua user.
     */
    public function isActive(): bool
    {
        return $this->is_active !== false;
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function hasPermission(string $key): bool
    {
        return $this->role?->hasPermission($key) ?? false;
    }

    /** URL gambar avatar (versi ikut waktu ubah agar cache browser diperbarui), atau null. */
    public function avatarUrl(): ?string
    {
        // Foto pribadi diutamakan; bila tidak ada, avatar mini; bila tidak ada juga, inisial (null).
        if ($this->avatar_photo) {
            // Nama file acak per unggahan → URL berubah, cache browser ikut diperbarui.
            return route('avatar.photo', ['user' => $this->id, 'v' => substr(md5($this->avatar_photo), 0, 10)]);
        }

        return $this->avatar_updated_at
            ? route('avatar.show', ['user' => $this->id, 'v' => $this->avatar_updated_at->timestamp])
            : null;
    }

    /**
     * Nama untuk TAMPILAN: emoji/simbol hiasan dibuang agar dashboard rapi.
     * Data asli tetap tersimpan; form edit memakai getRawOriginal('name').
     */
    protected function name(): Attribute
    {
        return Attribute::get(fn (?string $value) => \App\Support\TeksTampil::bersih($value));
    }
}
