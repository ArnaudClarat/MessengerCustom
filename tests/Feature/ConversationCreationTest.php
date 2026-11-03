<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\Schema\CreateMessagingSchema;

require_once __DIR__ . '/../Support/Schema/CreateMessagingSchema.php';

// Préparer une base SQLite indépendante pour chaque test.
beforeEach(function () {

    // Vérifier que nous utilisons exclusivement SQLite en mémoire.
    if (
        config('database.default') !== 'sqlite' ||
        config('database.connections.sqlite.database') !== ':memory:'
    ) {
        throw new RuntimeException(
            'Sécurité : les tests doivent utiliser SQLite en mémoire.'
        );
    }

    // Créer les tables nécessaires à nos tests.
    CreateMessagingSchema::create();
});

test('Alice peut créer une conversation directe avec Bob', function () {

    // 1. Créer deux utilisateurs.
    $alice = User::create([
        'id' => (string) Str::uuid(),
        'email' => 'alice@example.test',
        'display_name' => 'Alice',
    ]);

    $bob = User::create([
        'id' => (string) Str::uuid(),
        'email' => 'bob@example.test',
        'display_name' => 'Bob',
    ]);

    // 2. Créer une organisation commune.
    $orgId = (string) Str::uuid();

    DB::table('organizations')->insert([
        'id' => $orgId,
        'name' => 'Organisation de test',
        'slug' => 'organisation-test',
    ]);

    // 3. Ajouter Alice et Bob comme membres actifs.
    foreach ([$alice, $bob] as $user) {
        DB::table('org_members')->insert([
            'org_id' => $orgId,
            'user_id' => $user->id,
            'role' => 'member',
            'status' => 'active',
        ]);
    }

    // 4. Simuler une requête HTTP authentifiée.
    $response = $this->actingAs($alice)->post(
        route('conversations.store'),
        ['recipient_id' => $bob->id]
    );

    // 5. Vérifier la redirection.
    $response->assertRedirect(route('conversations.index'));

    // 6. Vérifier qu'une conversation a été créée.
    $this->assertDatabaseCount('conversations', 1);

    // 7. Vérifier les informations de la conversation.
    $conversation = DB::table('conversations')->first();

    expect($conversation->type)->toBe('direct');
    expect($conversation->visibility)->toBe('private');
    expect($conversation->org_id)->toBe($orgId);

    // 8. Vérifier que les deux utilisateurs sont participants.
    $this->assertDatabaseHas('conversation_members', [
        'conversation_id' => $conversation->id,
        'user_id' => $alice->id,
    ]);

    $this->assertDatabaseHas('conversation_members', [
        'conversation_id' => $conversation->id,
        'user_id' => $bob->id,
    ]);
});



test('une conversation directe existante ne doit pas être dupliquée', function () {

    // Créer Alice et Bob.
    $alice = User::create([
        'email' => 'alice@example.test',
        'display_name' => 'Alice',
    ]);

    $bob = User::create([
        'email' => 'bob@example.test',
        'display_name' => 'Bob',
    ]);

    // Créer leur organisation commune.
    $orgId = (string) Str::uuid();

    DB::table('organizations')->insert([
        'id' => $orgId,
        'name' => 'Organisation de test',
        'slug' => 'organisation-test',
    ]);

    // Les deux utilisateurs sont membres actifs.
    foreach ([$alice, $bob] as $user) {
        DB::table('org_members')->insert([
            'org_id' => $orgId,
            'user_id' => $user->id,
            'status' => 'active',
        ]);
    }

    // Première demande : la conversation est créée.
    $this->actingAs($alice)
        ->post(route('conversations.store'), [
            'recipient_id' => $bob->id,
        ])
        ->assertRedirect(route('conversations.index'));

    // Deuxième demande : la conversation existante est réutilisée.
    $this->actingAs($alice)
        ->post(route('conversations.store'), [
            'recipient_id' => $bob->id,
        ])
        ->assertRedirect(route('conversations.index'));

    // Il ne doit exister qu'une seule conversation.
    $this->assertDatabaseCount('conversations', 1);
    $this->assertDatabaseCount('conversation_members', 2);
});


test('un utilisateur ne peut pas créer une conversation avec lui-même', function () {

    // Créer Alice.
    $alice = User::create([
        'email' => 'alice@example.test',
        'display_name' => 'Alice',
    ]);

    // Alice essaie de se sélectionner comme destinataire.
    $response = $this->actingAs($alice)
        ->post(route('conversations.store'), [
            'recipient_id' => $alice->id,
        ]);

    // Laravel doit refuser la requête.
    $response->assertSessionHasErrors('recipient_id');

    // Aucune conversation ne doit être créée.
    $this->assertDatabaseCount('conversations', 0);
});


test('un utilisateur ne peut pas discuter avec un membre extérieur à son organisation', function () {

    // Créer Alice et Charlie.
    $alice = User::create([
        'email' => 'alice@example.test',
        'display_name' => 'Alice',
    ]);

    $charlie = User::create([
        'email' => 'charlie@example.test',
        'display_name' => 'Charlie',
    ]);

    // Créer deux organisations différentes.
    $orgAlice = (string) Str::uuid();
    $orgCharlie = (string) Str::uuid();

    DB::table('organizations')->insert([
        [
            'id' => $orgAlice,
            'name' => 'Organisation Alice',
            'slug' => 'organisation-alice',
        ],
        [
            'id' => $orgCharlie,
            'name' => 'Organisation Charlie',
            'slug' => 'organisation-charlie',
        ],
    ]);

    // Alice et Charlie appartiennent à des organisations distinctes.
    DB::table('org_members')->insert([
        [
            'org_id' => $orgAlice,
            'user_id' => $alice->id,
            'status' => 'active',
        ],
        [
            'org_id' => $orgCharlie,
            'user_id' => $charlie->id,
            'status' => 'active',
        ],
    ]);

    // Alice tente de créer une conversation avec Charlie.
    $response = $this->actingAs($alice)
        ->post(route('conversations.store'), [
            'recipient_id' => $charlie->id,
        ]);

    // L'accès doit être refusé.
    $response->assertSessionHasErrors('recipient_id');

    // Aucune conversation ne doit être créée.
    $this->assertDatabaseCount('conversations', 0);
});
