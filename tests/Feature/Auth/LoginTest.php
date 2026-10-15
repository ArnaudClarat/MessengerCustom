<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class LoginTest extends TestCase
{
    /**
     * Un visiteur non connecté ne peut pas accéder au Dashboard.
     */
    public function test_un_visiteur_est_redirige_vers_la_connexion(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');

        $this->assertGuest();
    }

    /**
     * Un utilisateur avec des identifiants valides peut se connecter.
     */
    public function test_un_utilisateur_peut_se_connecter(): void
    {
        // Crée une table uniquement dans SQLite en mémoire.
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email')->unique();
            $table->string('display_name');
            $table->string('password_hash')->nullable();
            $table->string('avatar_url')->nullable();
            $table->boolean('is_bot')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // Crée un utilisateur fictif dans la base temporaire.
        $user = User::create([
            'id' => (string) Str::uuid(),
            'email' => 'test@example.com',
            'display_name' => 'Utilisateur Test',
            'password_hash' => Hash::make('MotDePasse123!'),
            'is_bot' => false,
        ]);

        // Simule l'envoi du formulaire de connexion.
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'MotDePasse123!',
        ]);

        // Vérifie la redirection après connexion.
        $response->assertRedirect('/dashboard');

        // Vérifie que l'utilisateur est authentifié.
        $this->assertAuthenticatedAs($user);
    }


    /**
     * Vérifie qu'un utilisateur ne peut pas se connecter
     * avec un mot de passe incorrect.
     */
    public function test_un_mot_de_passe_incorrect_est_refuse(): void
    {
        // Crée une table users temporaire dans SQLite.
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email')->unique();
            $table->string('display_name');
            $table->string('password_hash')->nullable();
            $table->string('avatar_url')->nullable();
            $table->boolean('is_bot')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // Crée un utilisateur fictif avec un mot de passe connu.
        User::create([
            'id' => (string) Str::uuid(),
            'email' => 'test@example.com',
            'display_name' => 'Utilisateur Test',
            'password_hash' => Hash::make('MotDePasse123!'),
            'is_bot' => false,
        ]);

        // Essaie de se connecter avec un mauvais mot de passe.
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'MauvaisMotDePasse',
        ]);

        // Vérifie que Laravel refuse la connexion.
        $response->assertSessionHasErrors('email');

        // Vérifie que l'utilisateur n'est pas connecté.
        $this->assertGuest();
    }


    /**
     * Vérifie qu'un utilisateur connecté peut se déconnecter.
     */
    public function test_un_utilisateur_peut_se_deconnecter(): void
    {
        // Crée un utilisateur fictif uniquement en mémoire.
        $user = new User();

        $user->id = (string) Str::uuid();
        $user->email = 'test@example.com';
        $user->display_name = 'Utilisateur Test';

        // Simule un utilisateur déjà authentifié.
        $this->actingAs($user);

        // Vérifie qu'il est connecté.
        $this->assertAuthenticated();

        // Simule une demande de déconnexion.
        $response = $this->post('/logout');

        // Vérifie la redirection vers la page de connexion.
        $response->assertRedirect('/login');

        // Vérifie que l'utilisateur est déconnecté.
        $this->assertGuest();

        // Vérifie que le Dashboard est à nouveau protégé.
        $this->get('/dashboard')->assertRedirect('/login');
    }


}
