<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\Schema\CreateMessagingSchema;

// Charger la classe utilisée pour créer les tables SQLite.
require_once __DIR__ . '/../Support/Schema/CreateMessagingSchema.php';


// ======================================================
// 1. PRÉPARATION DE L'ENVIRONNEMENT DE TEST
// ======================================================

beforeEach(function () {

    // --------------------------------------------------
    // 1.1. PROTECTION DE LA BASE DE DONNÉES
    // --------------------------------------------------

    // Ces tests doivent utiliser uniquement SQLite
    // en mémoire afin de protéger PostgreSQL.
    if (
        config('database.default') !== 'sqlite' ||
        config('database.connections.sqlite.database') !== ':memory:'
    ) {
        throw new RuntimeException(
            'Sécurité : SQLite en mémoire est obligatoire.'
        );
    }

    // --------------------------------------------------
    // 1.2. CONFIGURATION DU BROADCASTING
    // --------------------------------------------------

    // Utiliser Reverb pour tester les autorisations
    // réelles des canaux privés.
    config()->set('broadcasting.default', 'reverb');

    // Identifiants fictifs utilisés uniquement
    // dans l'environnement de test.
    config()->set(
        'broadcasting.connections.reverb.key',
        'test-key'
    );

    config()->set(
        'broadcasting.connections.reverb.secret',
        'test-secret'
    );

    config()->set(
        'broadcasting.connections.reverb.app_id',
        'test-app'
    );

    // Réinitialiser les diffuseurs déjà initialisés.
    app(BroadcastManager::class)->forgetDrivers();

    // --------------------------------------------------
    // 1.3. ENREGISTREMENT DES RÈGLES DE CANAUX
    // --------------------------------------------------

    // Charger les règles définies dans routes/channels.php.
    //
    // Cela garantit que les règles d'autorisation
    // sont disponibles pendant les tests.
    //
    // Le fichier doit notamment définir le canal :
    // conversation.{conversationId}
    require base_path('routes/channels.php');

    // --------------------------------------------------
    // 1.4. CRÉATION DES TABLES SQLITE
    // --------------------------------------------------

    CreateMessagingSchema::create();

    // --------------------------------------------------
    // 1.5. CRÉATION DES UTILISATEURS
    // --------------------------------------------------

    // Alice : première participante.
    $this->alice = User::create([
        'email' => 'alice@example.test',
        'display_name' => 'Alice',
    ]);

    // Bob : deuxième participant.
    $this->bob = User::create([
        'email' => 'bob@example.test',
        'display_name' => 'Bob',
    ]);

    // Charlie : utilisateur extérieur à la conversation.
    $this->charlie = User::create([
        'email' => 'charlie@example.test',
        'display_name' => 'Charlie',
    ]);

    // --------------------------------------------------
    // 1.6. CRÉATION DE L'ORGANISATION
    // --------------------------------------------------

    $this->orgId = (string) Str::uuid();

    DB::table('organizations')->insert([
        'id' => $this->orgId,
        'name' => 'Organisation de test',
        'slug' => 'organisation-test',
    ]);

    // --------------------------------------------------
    // 1.7. AJOUT DES MEMBRES À L'ORGANISATION
    // --------------------------------------------------

    // Alice et Bob sont membres actifs.
    // Charlie n'appartient pas à cette organisation.
    foreach ([$this->alice, $this->bob] as $user) {

        DB::table('org_members')->insert([
            'org_id' => $this->orgId,
            'user_id' => $user->id,
            'role' => 'member',
            'status' => 'active',
        ]);
    }

    // --------------------------------------------------
    // 1.8. CRÉATION DE LA CONVERSATION
    // --------------------------------------------------

    // Trier les identifiants pour générer une clé
    // indépendante de l'ordre des participants.
    $ids = [
        $this->alice->id,
        $this->bob->id,
    ];

    sort($ids, SORT_STRING);

    // Créer une conversation directe et privée.
    $this->conversation = Conversation::create([
        'id' => (string) Str::uuid(),
        'org_id' => $this->orgId,
        'type' => 'direct',
        'visibility' => 'private',
        'direct_key' => implode(':', $ids),
        'created_by' => $this->alice->id,
    ]);

    // --------------------------------------------------
    // 1.9. AJOUT DES PARTICIPANTS À LA CONVERSATION
    // --------------------------------------------------

    // Alice et Bob peuvent participer.
    // Charlie n'est pas ajouté à cette conversation.
    $this->conversation->members()->attach([

        $this->alice->id => [
            'org_id' => $this->orgId,
            'role' => 'admin',
            'muted' => false,
            'joined_at' => now(),
        ],

        $this->bob->id => [
            'org_id' => $this->orgId,
            'role' => 'member',
            'muted' => false,
            'joined_at' => now(),
        ],
    ]);
});


// ======================================================
// 2. FONCTION UTILITAIRE
// ======================================================

/**
 * Préparer une demande d'autorisation WebSocket.
 *
 * socket_id :
 * Identifiant fictif de la connexion WebSocket.
 *
 * channel_name :
 * Nom complet du canal privé.
 *
 * Laravel Echo utilise le préfixe "private-"
 * pour les canaux privés.
 */
function requestConversationChannel(string $conversationId): array
{
    return [
        'socket_id' => '1234.5678',
        'channel_name' => 'private-conversation.' . $conversationId,
    ];
}


// ======================================================
// 3. TEST : VÉRIFICATION DES RÈGLES ENREGISTRÉES
// ======================================================

test('la règle du canal privé conversation est enregistrée', function () {

    // Récupérer les règles d'autorisation connues
    // par le diffuseur utilisé pendant le test.
    $channels = Broadcast::getChannels();

    // Vérifier que le canal de conversation existe.
    expect($channels)
        ->toHaveKey('conversation.{conversationId}');
});


// ======================================================
// 4. TEST : ALICE EST AUTORISÉE
// ======================================================

test('Alice peut accéder au canal privé de sa conversation', function () {

    // Vérifier qu'Alice appartient bien à la conversation.
    $isMember = $this->alice->conversations()
        ->where('conversations.id', $this->conversation->id)
        ->exists();

    expect($isMember)->toBeTrue();

    // Simuler une requête d'Alice authentifiée.
    $response = $this->actingAs($this->alice)
        ->postJson(
            '/broadcasting/auth',
            requestConversationChannel($this->conversation->id)
        );

    // Alice doit être autorisée.
    $response->assertOk();

    // Vérifier qu'une signature a été générée.
    $response->assertJsonStructure(['auth']);

    expect($response->json('auth'))
        ->toBeString()
        ->not->toBeEmpty();
});


// ======================================================
// 5. TEST : BOB EST AUTORISÉ
// ======================================================

test('Bob peut accéder au canal privé de sa conversation', function () {

    // Vérifier que Bob appartient à la conversation.
    $isMember = $this->bob->conversations()
        ->where('conversations.id', $this->conversation->id)
        ->exists();

    expect($isMember)->toBeTrue();

    // Simuler une requête authentifiée de Bob.
    $response = $this->actingAs($this->bob)
        ->postJson(
            '/broadcasting/auth',
            requestConversationChannel($this->conversation->id)
        );

    // Bob doit être autorisé.
    $response->assertOk();

    // Une signature d'autorisation doit être présente.
    $response->assertJsonStructure(['auth']);

    expect($response->json('auth'))
        ->toBeString()
        ->not->toBeEmpty();
});


// ======================================================
// 6. TEST : CHARLIE EST REFUSÉ
// ======================================================

test('Charlie ne peut pas accéder au canal privé Alice Bob', function () {

    // Vérifier que Charlie n'est pas participant.
    $isMember = $this->charlie->conversations()
        ->where('conversations.id', $this->conversation->id)
        ->exists();

    expect($isMember)->toBeFalse();

    // Charlie tente de rejoindre le canal privé.
    $response = $this->actingAs($this->charlie)
        ->postJson(
            '/broadcasting/auth',
            requestConversationChannel($this->conversation->id)
        );

    // L'accès doit être refusé.
    $response->assertForbidden();

    // Aucune signature ne doit être générée.
    expect($response->json('auth'))->toBeNull();
});


// ======================================================
// 7. TEST : VISITEUR NON AUTHENTIFIÉ
// ======================================================

test('un visiteur non connecté ne peut pas accéder au canal privé', function () {

    // Aucune authentification n'est effectuée.
    $response = $this->postJson(
        '/broadcasting/auth',
        requestConversationChannel($this->conversation->id)
    );

    // Selon le middleware, Laravel peut retourner :
    // 401 : utilisateur non authentifié.
    // 403 : accès au canal interdit.
    expect($response->status())->toBeIn([401, 403]);

    // Le visiteur ne doit recevoir aucune signature.
    expect($response->json('auth'))->toBeNull();
});
