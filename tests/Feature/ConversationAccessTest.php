<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\Schema\CreateMessagingSchema;

require_once __DIR__ . '/../Support/Schema/CreateMessagingSchema.php';

// ======================================================
// PRÉPARATION DE LA BASE SQLITE
// ======================================================

beforeEach(function () {

    // Empêcher toute exécution sur PostgreSQL.
    if (
        config('database.default') !== 'sqlite' ||
        config('database.connections.sqlite.database') !== ':memory:'
    ) {
        throw new RuntimeException(
            'Sécurité : SQLite en mémoire est obligatoire.'
        );
    }

    // Construire les tables nécessaires aux tests.
    CreateMessagingSchema::create();

    // Créer trois utilisateurs.
    $this->alice = User::create([
        'email' => 'alice@example.test',
        'display_name' => 'Alice',
    ]);

    $this->bob = User::create([
        'email' => 'bob@example.test',
        'display_name' => 'Bob',
    ]);

    $this->charlie = User::create([
        'email' => 'charlie@example.test',
        'display_name' => 'Charlie',
    ]);

    // Créer une organisation.
    $this->orgId = (string) Str::uuid();

    DB::table('organizations')->insert([
        'id' => $this->orgId,
        'name' => 'Organisation de test',
        'slug' => 'organisation-test',
    ]);

    // Alice et Bob appartiennent à la même organisation.
    foreach ([$this->alice, $this->bob] as $user) {
        DB::table('org_members')->insert([
            'org_id' => $this->orgId,
            'user_id' => $user->id,
            'role' => 'member',
            'status' => 'active',
        ]);
    }

    // Créer une conversation directe entre Alice et Bob.
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

    // Ajouter les deux participants.
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
// TEST 1 : ALICE VOIT SA CONVERSATION
// ======================================================

test('Alice peut consulter sa conversation avec Bob', function () {

    $this->actingAs($this->alice)
        ->get(route('conversations.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Conversations/Index')
            ->has('conversationsFromDb', 1)
            ->where('conversationsFromDb.0.id', $this->conversation->id)
            ->where('conversationsFromDb.0.name', 'Bob')
        );
});


// ======================================================
// TEST 2 : CHARLIE NE VOIT PAS LA CONVERSATION
// ======================================================

test('Charlie ne peut pas voir la conversation Alice Bob', function () {

    $this->actingAs($this->charlie)
        ->get(route('conversations.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Conversations/Index')
            ->has('conversationsFromDb', 0)
        );
});


// ======================================================
// TEST 3 : DESTINATAIRES DISPONIBLES
// ======================================================

test('Alice voit Bob dans les destinataires disponibles', function () {

    $this->actingAs($this->alice)
        ->get(route('conversations.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Conversations/Index')
            ->has('availableUsers', 1)
            ->where('availableUsers.0.id', $this->bob->id)
            ->where('availableUsers.0.display_name', 'Bob')
        );
});


// ======================================================
// TEST 4 : UTILISATEUR NON CONNECTÉ
// ======================================================

test('un visiteur non connecté ne peut pas accéder aux conversations', function () {

    $this->get(route('conversations.index'))
        ->assertRedirect(route('login'));
});
