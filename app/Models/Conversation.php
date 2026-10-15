<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    // La table PostgreSQL existe déjà.
    protected $table = 'conversations';

    // L'identifiant est un UUID, pas un entier.
    protected $keyType = 'string';
    public $incrementing = false;

    // PostgreSQL possède created_at, mais pas updated_at.
    public const UPDATED_AT = null;

    // Les attributs qui pourront être renseignés
    // explicitement lors de la création.
    protected $fillable = [
        'org_id',
        'type',
        'visibility',
        'name',
        'topic',
        'direct_key',
        'created_by',
    ];

    // Une conversation possède plusieurs messages.
    public function messages(): HasMany
    {
        return $this->hasMany(
            Message::class,
            'conversation_id'
        );
    }

    // Une conversation possède plusieurs participants.
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'conversation_members',
            'conversation_id',
            'user_id'
        )->withPivot(
            'org_id',
            'role',
            'last_read_message_id',
            'muted',
            'joined_at'
        );
    }

    // Une conversation peut avoir un créateur.
    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
}
