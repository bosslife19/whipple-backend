<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [];

    public function createdGames()
    {
        return $this->hasMany(Game::class, 'creator_id');
    }

    public function transaction()
    {
        return $this->hasMany(Transaction::class, 'user_id');
    }

    public function bank()
    {
        return $this->hasMany(UserBankDetails::class, 'user_id');
    }

    public function playedGames()
    {
        return $this->belongsToMany(Game::class, 'game_user')
            ->withTimestamps();
    }

    public function vote()
    {
        return $this->hasOne(Vote::class, 'player_id');
    }

    public function playerskillgame()
    {
        return $this->hasMany(SkillGameMatchPlayers::class, 'match_id');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'can_access_admin' => 'boolean',
            'admin_permissions' => 'array',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->can_access_admin;
    }

    public function hasAdminPermission(string $key): bool
    {
        if (! $this->can_access_admin) {
            return false;
        }
        if ($this->admin_role === 'master') {
            return true;
        }
        $permissions = $this->admin_permissions;
        if (! is_array($permissions)) {
            return false;
        }

        return in_array('*', $permissions, true) || in_array($key, $permissions, true);
    }
}
