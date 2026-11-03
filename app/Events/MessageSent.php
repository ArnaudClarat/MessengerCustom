<?php

namespace App\Events;

// Modèle Eloquent représentant un message enregistré en base.
use App\Models\Message;

// Permet de diffuser un événement sans attendre un worker de queue.
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

// Représente un canal WebSocket privé.
use Illuminate\Broadcasting\PrivateChannel;

// Fournit les méthodes nécessaires à la diffusion et aux sockets.
use Illuminate\Broadcasting\InteractsWithSockets;

// Permet de déclencher l'événement avec MessageSent::dispatch().
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Événement déclenché lorsqu'un utilisateur envoie un message.
 *
 * ShouldBroadcastNow indique à Laravel de diffuser
 * immédiatement cet événement via Laravel Reverb.
 */
class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    /**
     * Constructeur de l'événement.
     *
     * Le message est transmis depuis ConversationController.
     * Il contient notamment :
     * - son identifiant ;
     * - l'identifiant de la conversation ;
     * - l'identifiant de l'expéditeur ;
     * - le contenu du message.
     */
    public function __construct(
        public Message $message
    ) {
    }

    /**
     * Déterminer sur quel canal diffuser le message.
     *
     * Chaque conversation possède son propre canal privé.
     * Son identifiant est un UUID dans MessengerCustom.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                'conversation.' . $this->message->conversation_id
            ),
        ];
    }

    /**
     * Définir le nom de l'événement reçu par Vue.js.
     *
     * Côté Laravel Echo, nous écouterons ".message.sent".
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Déterminer les données transmises aux navigateurs.
     *
     * Nous transmettons uniquement les informations utiles
     * à l'affichage du nouveau message.
     */
    public function broadcastWith(): array
    {
        // Charger l'expéditeur s'il n'est pas déjà chargé.
        $this->message->loadMissing('sender');

        return [
            // Identifiant unique du message.
            'id' => $this->message->id,

            // UUID de la conversation concernée.
            'conversation_id' => $this->message->conversation_id,

            // Nom de l'expéditeur affiché dans Vue.
            'author' => $this->message->sender?->display_name
                ?? 'Utilisateur inconnu',

            // Contenu textuel du message.
            'text' => $this->message->content,

            // Identifiant de l'expéditeur.
            'sender_id' => $this->message->sender_id,

            // Date de création au format ISO 8601.
            'date' => $this->message->created_at?->toISOString(),
        ];
    }
}
