<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthIdentity extends Model
{
    use HasUuids;

    protected $table = 'auth_identities';

    // La table ne possède pas de colonne updated_at.
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'provider',
        'provider_user_id',
    ];

    /**
     * Retourne l'utilisateur auquel appartient cette identité externe.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
