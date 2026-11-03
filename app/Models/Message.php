<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    // Nom de la table PostgreSQL existante.
    protected $table = 'messages';

    // La clé primaire est un BIGINT.
    protected $primaryKey = 'id';
    protected $keyType = 'int';

    // La table possède created_at, mais pas updated_at.
    public const UPDATED_AT = null;

    // Colonnes pouvant être renseignées explicitement.
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'type',
        'content',
        'reply_to_id',
    ];

    // Un message appartient à une conversation.
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(
            Conversation::class,
            'conversation_id'
        );
    }

    // Un message peut avoir un auteur.
    public function sender(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'sender_id'
        );
    }

    // Un message peut être une réponse à un autre message.
    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(
            Message::class,
            'reply_to_id'
        );
    }
}
