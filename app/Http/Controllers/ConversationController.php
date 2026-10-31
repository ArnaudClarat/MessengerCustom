<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Events\MessageSent; // Import de l'événement qui diffusera les messages via Reverb.

class ConversationController extends Controller
{
    /**
     * Affiche les conversations de l'utilisateur connecté.
     *
     * Les données proviennent de PostgreSQL via Eloquent.
     *
     * Cette méthode ne modifie aucune donnée.
     */
    public function index(Request $request): Response
    {
        // ==================================================
        // 1. RÉCUPÉRER L'UTILISATEUR CONNECTÉ
        // ==================================================

        $user = $request->user();


        // ==================================================
        // 2. RÉCUPÉRER SES CONVERSATIONS
        // ==================================================

        // La relation conversations() du modèle User permet de récupérer uniquement les conversations
        // dont l'utilisateur est membre.

        $conversations = $user->conversations()

            // Chargement des relations associées.
            ->with([

                // Participants de chaque conversation.
                // Cette relation doit exister dans App/Models/Conversation.php.
                'members',

                // Messages de chaque conversation.
                'messages' => function ($query) {

                    $query

                        // Exclure les messages supprimés.
                        ->whereNull('deleted_at')

                        // Trier par identifiant croissant.
                        ->orderBy('id', 'asc')

                        // Charger l'expéditeur.
                        ->with('sender');
                },
            ])

            // Exécuter la requête SQL.
            ->get();


        // ==================================================
        // 3. PRÉPARER LES DONNÉES POUR VUE.JS
        // ==================================================

        $conversationsForVue = $conversations->map(
            function ($conversation) use ($user) {

                // Récupérer les participants.
                $members = $conversation->members;

                // Nom affiché dans l'interface.
                $displayName = $conversation->name;

                // Pour une conversation directe, afficher le nom de l'autre utilisateur.
                if ($conversation->type === 'direct') {

                    $otherUser = $members->first(
                        fn ($member) => $member->id !== $user->id
                    );

                    $displayName = $otherUser?->display_name
                        ?? 'Utilisateur inconnu';
                }

                // Pour les groupes sans nom.
                if ($conversation->type === 'group'
                    && empty($displayName)) {

                    $displayName = 'Conversation de groupe';
                }

                // Pour les canaux sans nom.
                if ($conversation->type === 'channel'
                    && empty($displayName)) {

                    $displayName = 'Canal de discussion';
                }

                // Transformer les messages dans le format attendu par notre composant Vue.
                $messages = $conversation->messages->map(
                    function ($message) use ($user) {

                        return [
                            'id' => $message->id,

                            'author' => $message->sender?->display_name
                                ?? 'Utilisateur inconnu',

                            'text' => $message->content,

                            // true si l'utilisateur connecté
                            // est l'expéditeur du message.
                            'mine' => $message->sender_id === $user->id,

                            'date' => $message->created_at,
                        ];
                    }
                )->values();

                // Structure finale d'une conversation.
                return [
                    'id' => $conversation->id,
                    'name' => $displayName,
                    'type' => $conversation->type,
                    'visibility' => $conversation->visibility,

                    'members' => $members
                        ->pluck('display_name')
                        ->values(),

                    'messages' => $messages,
                ];
            }
        )->values();


        // ==================================================
        // 4. TRANSMETTRE LES DONNÉES À INERTIA
        // ==================================================

        return Inertia::render('Conversations/Index', [

            // Informations de l'utilisateur connecté.
            'user' => [
                'id' => $user->id,
                'display_name' => $user->display_name,
            ],

            // Conversations provenant de PostgreSQL.
            'conversationsFromDb' => $conversationsForVue,
        ]);
    }


    /**
     * Enregistre un nouveau message dans une conversation.
     *
     * Sécurité :
     * - L'utilisateur doit être authentifié.
     * - Il doit appartenir à la conversation.
     * - Le contenu doit respecter les règles de validation.
     */
    public function storeMessage(Request $request, string $conversation)
    {
        // ==================================================
        // 1. RÉCUPÉRER L'UTILISATEUR CONNECTÉ
        // ==================================================

        $user = $request->user();


        // ==================================================
        // 2. VÉRIFIER L'ACCÈS À LA CONVERSATION
        // ==================================================

        // Rechercher la conversation uniquement parmi celles auxquelles l'utilisateur connecté appartient.
        // Si elle n'existe pas ou si l'utilisateur n'est pas membre, Laravel renvoie une erreur HTTP 404.

        $conversationModel = $user->conversations()
            ->where('conversations.id', $conversation)
            ->firstOrFail();


        // ==================================================
        // 3. VALIDER LE CONTENU DU MESSAGE
        // ==================================================

        $validated = $request->validate([
            'content' => [
                'required',
                'string',
                'max:4000',
            ],
        ]);


        // ==================================================
        // 4. ENREGISTRER LE MESSAGE
        // ==================================================

        // La conversation et l'expéditeur proviennent du
        // serveur : le navigateur ne peut pas les imposer.

        // Enregistrer le message dans PostgreSQL.

        // La méthode create() retourne une instance du modèle Message.
        // Nous la conservons dans $message pour pouvoir la diffuser.
        $message = $conversationModel->messages()->create([
            // L'expéditeur est toujours l'utilisateur authentifié.
            'sender_id' => $user->id,

            // Type de message défini par notre application.
            'type' => 'user',

            // Contenu préalablement validé par Laravel.
            'content' => $validated['content'],
        ]);

        // Déclencher l'événement après l'enregistrement.
        // Comme MessageSent implémente ShouldBroadcastNow, Laravel transmet immédiatement l'événement à Reverb.
        MessageSent::dispatch($message);

        // Rediriger vers la liste des conversations.
        // Inertia pourra actualiser les données après l'envoi.
        return redirect()->route('conversations.index');


        // ==================================================
        // 5. RETOURNER À LA PAGE DES CONVERSATIONS
        // ==================================================

        // Inertia pourra actualiser les données et afficher le nouveau message après la redirection.
        return redirect()->route('conversations.index');
    }

}
