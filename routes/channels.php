<?php

use Illuminate\Support\Facades\Broadcast;

// ======================================================
// CANAL PRIVÉ D'UN UTILISATEUR
// ======================================================

// Vérifier que l'utilisateur connecté est bien le propriétaire du canal demandé.
Broadcast::channel('App.Models.User.{id}', function ($user, string $id) {
    return (string) $user->id === $id;
});


// ======================================================
// CANAL PRIVÉ D'UNE CONVERSATION
// ======================================================

// Autoriser uniquement les participants de la conversation à recevoir ses événements.
Broadcast::channel('conversation.{conversationId}', function ($user, string $conversationId) {

    return $user->conversations()
        ->where('conversations.id', $conversationId)
        ->exists();
});
