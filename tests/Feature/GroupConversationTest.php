<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
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

    // Reconstruire les tables pour chaque test.
    CreateMessagingSchema::create();
});

// ======================================================
// TEST 1 : CRÉATION D'UN GROUPE
// ======================================================

test('Alice peut créer un groupe avec Bob et Charlie', function () {

    // Créer trois utilisateurs.
    $alice = User::create([
        'email' => 'alice@example.test',
        'display_name' => 'Alice',
    ]);

    $bob = User::create([
        'email' => 'bob@example.test',
        'display_name' => 'Bob',
    ]);

    $charlie = User::create([
        'email' => 'charlie@example.test',
        'display_name' => 'Charlie',
    ]);

    // Créer une organisation.
    $orgId = (string) Str::uuid();

    DB::table('organizations')->insert([
        'id' => $orgId,
        'name' => 'Organisation de test',
        'slug' => 'organisation-test',
    ]);

    // Ajouter les trois utilisateurs à l'organisation.
    foreach ([$alice, $bob, $charlie] as $user) {
        DB::table('org_members')->insert([
            'org_id' => $orgId,
            'user_id' => $user->id,
            'role' => 'member',
            'status' => 'active',
        ]);
    }

    // Alice envoie la demande de création du groupe.
    $response = $this->actingAs($alice)->post(
        route('conversations.groups.store'),
        [
            'name' => 'Projet intégration',
            'participant_ids' => [
                $bob->id,
                $charlie->id,
            ],
        ]
    );

    // Vérifier la redirection.
    $response->assertRedirect(route('conversations.index'));

    // Vérifier qu'une conversation a été créée.
    $this->assertDatabaseCount('conversations', 1);

    // Récupérer le groupe enregistré.
    $group = DB::table('conversations')->first();

    // Vérifier ses propriétés.
    expect($group->type)->toBe('group');
    expect($group->name)->toBe('Projet intégration');
    expect($group->visibility)->toBe('private');
    expect($group->org_id)->toBe($orgId);
    expect($group->created_by)->toBe($alice->id);
    expect($group->direct_key)->toBeNull();

    // Vérifier les trois membres.
    $this->assertDatabaseCount('conversation_members', 3);

    // Alice est administratrice.
    $this->assertDatabaseHas('conversation_members', [
        'conversation_id' => $group->id,
        'user_id' => $alice->id,
        'role' => 'admin',
    ]);

    // Bob est membre.
    $this->assertDatabaseHas('conversation_members', [
        'conversation_id' => $group->id,
        'user_id' => $bob->id,
        'role' => 'member',
    ]);

    // Charlie est membre.
    $this->assertDatabaseHas('conversation_members', [
        'conversation_id' => $group->id,
        'user_id' => $charlie->id,
        'role' => 'member',
    ]);
});

// ======================================================
// TEST 2 : NOMBRE MINIMUM DE PARTICIPANTS
// ======================================================

test('un groupe nécessite au moins deux invités', function () {

    $alice = User::create([
        'email' => 'alice@example.test',
        'display_name' => 'Alice',
    ]);

    $bob = User::create([
        'email' => 'bob@example.test',
        'display_name' => 'Bob',
    ]);

    // Alice n'invite que Bob.
    $response = $this->actingAs($alice)->post(
        route('conversations.groups.store'),
        [
            'name' => 'Groupe incomplet',
            'participant_ids' => [$bob->id],
        ]
    );

    // La validation Laravel doit échouer.
    $response->assertSessionHasErrors('participant_ids');

    // Aucun groupe ne doit être créé.
    $this->assertDatabaseCount('conversations', 0);
    $this->assertDatabaseCount('conversation_members', 0);
});

// ======================================================
// TEST 3 : INTERDIRE LES INVITÉS EN DOUBLE
// ======================================================

test('un participant ne peut pas être invité deux fois', function () {

    $alice = User::create([
        'email' => 'alice@example.test',
        'display_name' => 'Alice',
    ]);

    $bob = User::create([
        'email' => 'bob@example.test',
        'display_name' => 'Bob',
    ]);

    // Bob apparaît deux fois.
    $response = $this->actingAs($alice)->post(
        route('conversations.groups.store'),
        [
            'name' => 'Groupe incorrect',
            'participant_ids' => [
                $bob->id,
                $bob->id,
            ],
        ]
    );

    // La règle "distinct" doit refuser la demande.
    $response->assertSessionHasErrors();

    // Aucune écriture dans la base.
    $this->assertDatabaseCount('conversations', 0);
    $this->assertDatabaseCount('conversation_members', 0);
});

// ======================================================
// TEST 4 : REFUSER UN MEMBRE EXTÉRIEUR
// ======================================================

test('un utilisateur extérieur à l organisation est refusé', function () {

    $alice = User::create([
        'email' => 'alice@example.test',
        'display_name' => 'Alice',
    ]);

    $bob = User::create([
        'email' => 'bob@example.test',
        'display_name' => 'Bob',
    ]);

    $charlie = User::create([
        'email' => 'charlie@example.test',
        'display_name' => 'Charlie',
    ]);

    // Deux organisations différentes.
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

    // Alice et Bob sont dans la première organisation.
    foreach ([$alice, $bob] as $user) {
        DB::table('org_members')->insert([
            'org_id' => $orgAlice,
            'user_id' => $user->id,
            'role' => 'member',
            'status' => 'active',
        ]);
    }

    // Charlie appartient à une autre organisation.
    DB::table('org_members')->insert([
        'org_id' => $orgCharlie,
        'user_id' => $charlie->id,
        'role' => 'member',
        'status' => 'active',
    ]);

    // Alice essaie d'inviter Bob et Charlie.
    $response = $this->actingAs($alice)->post(
        route('conversations.groups.store'),
        [
            'name' => 'Groupe interdit',
            'participant_ids' => [
                $bob->id,
                $charlie->id,
            ],
        ]
    );

    // L'organisation commune est obligatoire.
    $response->assertSessionHasErrors('participant_ids');

    // Aucun groupe incomplet ne doit subsister.
    $this->assertDatabaseCount('conversations', 0);
    $this->assertDatabaseCount('conversation_members', 0);
});
