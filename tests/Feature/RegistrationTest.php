<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Support\Schema\CreateMessagingSchema;

// Charger notre schéma SQLite réservé aux tests.
require_once __DIR__ . '/../Support/Schema/CreateMessagingSchema.php';

/*
|--------------------------------------------------------------------------
| PRÉPARATION DES TESTS
|--------------------------------------------------------------------------
|
| Chaque test utilise une base SQLite temporaire en mémoire.
| Aucune opération n'est autorisée sur PostgreSQL.
|
*/

beforeEach(function () {

    // Vérification de sécurité avant toute création de table.
    if (
        config('database.default') !== 'sqlite' ||
        config('database.connections.sqlite.database') !== ':memory:'
    ) {
        throw new RuntimeException(
            'Sécurité : les tests doivent utiliser SQLite en mémoire.'
        );
    }

    // Créer les tables nécessaires aux tests.
    CreateMessagingSchema::create();
});


/*
|--------------------------------------------------------------------------
| TEST 1 : INSCRIPTION RÉUSSIE
|--------------------------------------------------------------------------
|
| Un visiteur peut créer un compte.
| Laravel doit ensuite l'authentifier et le rediriger.
|
*/

it('permet à un visiteur de créer un compte', function () {

    $response = $this->post(route('register.store'), [
        'display_name' => 'Charlie',
        'email' => 'charlie@example.test',
        'password' => 'CharlieTest2026!',
        'password_confirmation' => 'CharlieTest2026!',
    ]);

    // L'inscription doit rediriger vers le Dashboard.
    $response->assertRedirect(route('dashboard'));

    // Vérifier que le compte existe.
    $this->assertDatabaseHas('users', [
        'email' => 'charlie@example.test',
        'display_name' => 'Charlie',
    ]);

    // Vérifier que Charlie est connecté.
    $user = User::where('email', 'charlie@example.test')->firstOrFail();

    $this->assertAuthenticatedAs($user);
});


/*
|--------------------------------------------------------------------------
| TEST 2 : SÉCURITÉ DU MOT DE PASSE
|--------------------------------------------------------------------------
|
| Le mot de passe ne doit jamais être enregistré en clair.
| Notre modèle utilise la colonne password_hash.
|
*/

it('enregistre le mot de passe sous forme hachée', function () {

    $password = 'CharlieTest2026!';

    $this->post(route('register.store'), [
        'display_name' => 'Charlie',
        'email' => 'charlie@example.test',
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    // Récupérer l'utilisateur créé.
    $user = User::where('email', 'charlie@example.test')->firstOrFail();

    // Le mot de passe enregistré doit être différent du texte initial.
    expect($user->password_hash)->not->toBe($password);

    // Vérifier que le hachage correspond au mot de passe.
    expect(Hash::check($password, $user->password_hash))->toBeTrue();
});


/*
|--------------------------------------------------------------------------
| TEST 3 : ADRESSE E-MAIL DÉJÀ UTILISÉE
|--------------------------------------------------------------------------
|
| Deux utilisateurs ne doivent pas partager la même adresse.
|
*/

it('refuse une adresse email déjà utilisée', function () {

    // Créer un premier utilisateur.
    User::create([
        'display_name' => 'Alice',
        'email' => 'alice@example.test',
        'password_hash' => Hash::make('AliceTest2026!'),
    ]);

    // Essayer de créer un deuxième compte avec la même adresse.
    $response = $this->post(route('register.store'), [
        'display_name' => 'Nouvelle Alice',
        'email' => 'alice@example.test',
        'password' => 'CharlieTest2026!',
        'password_confirmation' => 'CharlieTest2026!',
    ]);

    // Laravel doit retourner une erreur de validation.
    $response->assertSessionHasErrors('email');

    // Aucun deuxième utilisateur ne doit être créé.
    $this->assertDatabaseCount('users', 1);

    // L'utilisateur ne doit pas être connecté.
    $this->assertGuest();
});


/*
|--------------------------------------------------------------------------
| TEST 4 : CONFIRMATION DU MOT DE PASSE
|--------------------------------------------------------------------------
|
| Le champ password_confirmation doit correspondre
| exactement au mot de passe choisi.
|
*/

it('refuse une confirmation incorrecte du mot de passe', function () {

    $response = $this->post(route('register.store'), [
        'display_name' => 'Charlie',
        'email' => 'charlie@example.test',
        'password' => 'CharlieTest2026!',
        'password_confirmation' => 'MotDePasseDifferent2026!',
    ]);

    // Laravel doit signaler l'erreur.
    $response->assertSessionHasErrors('password');

    // Aucun utilisateur ne doit avoir été créé.
    $this->assertDatabaseCount('users', 0);

    // Aucune authentification ne doit avoir lieu.
    $this->assertGuest();
});
