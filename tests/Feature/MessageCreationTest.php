
<?php

use App\Models\User;
use App\Models\Conversation;
use App\Events\MessageSent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Support\Schema\CreateMessagingSchema;

require_once __DIR__ . '/../Support/Schema/CreateMessagingSchema.php';

// ======================================================
// PRÉPARATION DE LA BASE DE TEST
// ======================================================

beforeEach(function () {

    // Interdire toute exécution sur PostgreSQL.
    if (
        config('database.default') !== 'sqlite' ||
        config('database.connections.sqlite.database') !== ':memory:'
    ) {
        throw new RuntimeException(
            'Sécurité : SQLite en mémoire est obligatoire.'
        );
    }

    // Construire les tables pour ce test.
    CreateMessagingSchema::create();

    // Éviter une connexion réelle au serveur Reverb.
    Event::fake([MessageSent::class]);

    // Créer Alice et Bob.
    $this->alice = User::create([
        'email' => 'alice@example.test',
        'display_name' => 'Alice',
    ]);

    $this->bob = User::create([
        'email' => 'bob@example.test',
        'display_name' => 'Bob',
    ]);

    // Créer une organisation commune.
    $this->orgId = (string) Str::uuid();

    DB::table('organizations')->insert([
        'id' => $this->orgId,
        'name' => 'Organisation de test',
        'slug' => 'organisation-test',
    ]);

    // Ajouter les deux membres actifs.
    foreach ([$this->alice, $this->bob] as $user) {
        DB::table('org_members')->insert([
            'org_id' => $this->orgId,
            'user_id' => $user->id,
            'role' => 'member',
            'status' => 'active',
        ]);
    }

    // Créer une conversation directe.
    $ids = [$this->alice->id, $this->bob->id];
    sort($ids, SORT_STRING);

    $this->conversation = Conversation::create([
        'id' => (string) Str::uuid(),
        'org_id' => $this->orgId,
        'type' => 'direct',
        'visibility' => 'private',
        'direct_key' => implode(':', $ids),
        'created_by' => $this->alice->id,
    ]);

    // Associer Alice et Bob à la conversation.
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
// TEST 1 : ALICE ENVOIE UN MESSAGE
// ======================================================

test('Alice peut envoyer un message à Bob', function () {

    $response = $this->actingAs($this->alice)->post(
        route('conversations.messages.store', $this->conversation->id),
        ['content' => 'Bonjour Bob !']
    );

    $response->assertRedirect(route('conversations.index'));

    $this->assertDatabaseHas('messages', [
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->alice->id,
        'type' => 'user',
        'content' => 'Bonjour Bob !',
    ]);

    Event::assertDispatched(MessageSent::class);
});


// ======================================================
// TEST 2 : BOB RÉPOND À ALICE
// ======================================================

test('Bob peut répondre à Alice', function () {

    $this->actingAs($this->bob)->post(
        route('conversations.messages.store', $this->conversation->id),
        ['content' => 'Bonjour Alice !']
    )->assertRedirect(route('conversations.index'));

    $this->assertDatabaseHas('messages', [
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->bob->id,
        'content' => 'Bonjour Alice !',
    ]);
});


// ======================================================
// TEST 3 : CHARLIE N'A PAS ACCÈS À LA CONVERSATION
// ======================================================

test('Charlie ne peut pas écrire dans la conversation Alice Bob', function () {

    // Charlie existe, mais n'est pas participant.
    $charlie = User::create([
        'email' => 'charlie@example.test',
        'display_name' => 'Charlie',
    ]);

    $this->actingAs($charlie)->post(
        route('conversations.messages.store', $this->conversation->id),
        ['content' => 'Message non autorisé']
    )->assertNotFound();

    // Aucun message ne doit être enregistré.
    $this->assertDatabaseCount('messages', 0);
});


// ======================================================
// TEST 4 : MESSAGE VIDE
// ======================================================

test('Alice ne peut pas envoyer un message vide', function () {

    $this->actingAs($this->alice)->post(
        route('conversations.messages.store', $this->conversation->id),
        ['content' => '']
    )->assertSessionHasErrors('content');

    $this->assertDatabaseCount('messages', 0);
});


// ======================================================
// TEST 5 : MESSAGE TROP LONG
// ======================================================

test('Alice ne peut pas envoyer plus de 4000 caractères', function () {

    $this->actingAs($this->alice)->post(
        route('conversations.messages.store', $this->conversation->id),
        ['content' => str_repeat('A', 4001)]
    )->assertSessionHasErrors('content');

    $this->assertDatabaseCount('messages', 0);
});
