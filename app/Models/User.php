<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable;

    // Pas de colonne updated_at dans la table
    const UPDATED_AT = null;

    // Laravel cherchera le mot de passe dans password_hash
    protected $authPasswordName = 'password_hash';

    // Pas de colonne remember_token : désactive « Se souvenir de moi »
    protected $rememberTokenName = '';

    protected $fillable = [
        'email',
        'password_hash',
        'display_name',
        'avatar_url',
        'is_bot',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password_hash' => 'hashed',
            'is_bot' => 'boolean',
        ];
    }

    public function authIdentities(): HasMany
    {
        return $this->hasMany(AuthIdentity::class);
    }
}