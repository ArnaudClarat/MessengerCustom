<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Events\MessageSent; // Import de l'événement qui diffusera les messages via Reverb.
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


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

                            // true si l'utilisateur connecté est l'expéditeur du message.
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
        // 4. RÉCUPÉRER LES DESTINATAIRES DISPONIBLES
        // ==================================================

        // Rechercher les utilisateurs appartenant à au moins une organisation commune avec l'utilisateur connecté.
        // Les deux appartenances doivent être actives.
        $availableUsers = DB::table('users')
            ->join('org_members as recipient_member', 'users.id', '=', 'recipient_member.user_id')
            ->join('org_members as current_member', 'recipient_member.org_id', '=', 'current_member.org_id')
            ->where('current_member.user_id', $user->id)
            ->where('current_member.status', 'active')
            ->where('recipient_member.status', 'active')

            // Ne pas proposer à Alice de discuter avec elle-même.
            ->where('users.id', '!=', $user->id)

            // Ne transmettre que les données utiles à Vue.js.
            ->select('users.id', 'users.display_name')
            ->distinct()
            ->orderBy('users.display_name')
            ->get();

        // ==================================================
        // 4. TRANSMETTRE LES DONNÉES À INERTIA
        // ==================================================

        return Inertia::render('Conversations/Index', [

            // Informations de l'utilisateur connecté.
            'user' => [
                'id' => $user->id,
                'display_name' => $user->display_name,
            ],

            // Conversations provenant de PostgresSQL.
            'conversationsFromDb' => $conversationsForVue,

            // Liste des destinataires autorisés.
            'availableUsers' => $availableUsers,
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


    /**
     * Crée une conversation individuelle entre deux utilisateurs.
     *
     * Vérifications :
     * - L'utilisateur doit être connecté.
     * - Le destinataire doit exister et être différent de l'expéditeur.
     * - Les deux utilisateurs doivent appartenir à la même organisation active.
     * - Une conversation directe existante ne doit pas être dupliauée.
     */
    /**
     * Crée une conversation individuelle entre deux utilisateurs.
     *
     * Vérifications :
     * - L'utilisateur doit être authentifié.
     * - Le destinataire doit exister et être différent de l'expéditeur.
     * - Les deux utilisateurs doivent appartenir à la même organisation active.
     * - Une conversation directe existante ne doit pas être dupliquée.
     */
    public function store(Request $request)
    {
        // 1. Récupérer l'utilisateur connecté.
        $user = $request->user();

        // 2. Valider l'identifiant du destinataire.
        $validated = $request->validate([
            'recipient_id' => ['required', 'uuid', 'exists:users,id'],
        ]);

        $recipientId = $validated['recipient_id'];

        // Empêcher l'utilisateur de créer une discussion avec lui-même.
        if ($recipientId === $user->id) {
            throw ValidationException::withMessages([
                'recipient_id' => 'Vous ne pouvez pas créer une conversation avec vous-même.',
            ]);
        }

        // 3. Rechercher une organisation dans laquelle les deux
        // utilisateurs sont membres actifs.
        $orgId = DB::table('org_members as current_member')
            ->join('org_members as recipient_member', function ($join) use ($recipientId) {
                $join->on('current_member.org_id', '=', 'recipient_member.org_id')
                    ->where('recipient_member.user_id', '=', $recipientId)
                    ->where('recipient_member.status', '=', 'active');
            })
            ->where('current_member.user_id', $user->id)
            ->where('current_member.status', 'active')
            ->value('current_member.org_id');

        // Refuser la création en l'absence d'organisation commune.
        if (!$orgId) {
            throw ValidationException::withMessages([
                'recipient_id' => 'Cet utilisateur ne fait pas partie de votre organisation.',
            ]);
        }

        // 4. Construire une clé indépendante de l'ordre des utilisateurs.
        // Alice + Bob produisent la même clé que Bob + Alice.
        $ids = [$user->id, $recipientId];
        sort($ids, SORT_STRING);

        $directKey = implode(':', $ids);

        // 5. Exécuter la recherche et la création dans une transaction.
        DB::transaction(function () use ($user, $recipientId, $orgId, $directKey) {

            // Verrouiller l'organisation pendant la création. Cela sérialise les créations concurrentes dans cette organisation.
            DB::table('organizations')
                ->where('id', $orgId)
                ->lockForUpdate()
                ->first();

            // Vérifier si cette conversation existe déjà.
            $existingConversation = Conversation::query()
                ->where('org_id', $orgId)
                ->where('type', 'direct')
                ->where('direct_key', $directKey)
                ->first();

            // Si elle existe, aucune nouvelle conversation n'est créée.
            if ($existingConversation) {
                return;
            }

            // 6. Créer la conversation.
            // Nous générons explicitement l'UUID pour disposer de l'identifiant immédiatement dans Eloquent.
            $conversation = Conversation::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'org_id' => $orgId,
                'type' => 'direct',
                'visibility' => 'private',
                'direct_key' => $directKey,
                'created_by' => $user->id,
            ]);

            // 7. Associer les deux utilisateurs à la conversation.
            $conversation->members()->attach([
                $user->id => [
                    'org_id' => $orgId,
                    'role' => 'admin',
                    'muted' => false,
                    'joined_at' => now(),
                ],
                $recipientId => [
                    'org_id' => $orgId,
                    'role' => 'member',
                    'muted' => false,
                    'joined_at' => now(),
                ],
            ]);
        });

        // 8. Retourner à la liste des conversations.
        return redirect()->route('conversations.index');
    }



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

        // La conversation et l'expéditeur proviennent du serveur : le navigateur ne peut pas les imposer.

        // Enregistrer le message dans PostgreSQL.

        // La méthode create() retourne une instance du modèle Message. Nous la conservons dans $message pour pouvoir la diffuser.
        $message = $conversationModel->messages()->create([
            // L'expéditeur est toujours l'utilisateur authentifié.
            'sender_id' => $user->id,

            // Type de message défini par notre application.
            'type' => 'user',

            // Contenu préalablement validé par Laravel.
            'content' => $validated['content'],
        ]);

        // Déclencher l'événement après l'enregistrement. Comme MessageSent implémente ShouldBroadcastNow,
        // Laravel transmet immédiatement l'événement à Reverb.
        MessageSent::dispatch($message);


        // ==================================================
        // 5. RETOURNER À LA PAGE DES CONVERSATIONS
        // ==================================================


        // Rediriger vers la liste des conversations. Inertia pourra actualiser les données après l'envoi.
        return redirect()->route('conversations.index');

    }

}
