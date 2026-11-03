<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    /**
     * Affiche le formulaire Vue de création de compte.
    */
    public function show(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Valide, enregistre puis connecte le nouvel utilisateur.
    */

    public function store(Request $request): RedirectResponse
    {
        // ======================================================
        // 1. VALIDATION DES DONNÉES DU FORMULAIRE
        // ======================================================

        $validated = $request->validate([

            // Nom affiché de l'utilisateur.
            'display_name' => [
                'required',
                'string',
                'max:255',
            ],

            // Adresse e-mail obligatoire et unique.
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            // Mot de passe de 12 caractères minimum.
            // La règle confirmed exige password_confirmation.
            'password' => [
                'required',
                'confirmed',
                Password::min(12),
            ],
        ]);


        // ======================================================
        // 2. CRÉATION DE L'UTILISATEUR
        // ======================================================

        // User::create() insère l'utilisateur dans la table users.
        // La variable $user contient ensuite le modèle créé.

        // Notre application utilise password_hash plutôt que password.

        $user = User::create([
            'display_name' => $validated['display_name'],
            'email' => $validated['email'],
            'password_hash' => Hash::make($validated['password']),
        ]);


        // ======================================================
        // 3. AUTHENTIFICATION AUTOMATIQUE
        // ======================================================

        // Connecter l'utilisateur qui vient d'être créé.
        Auth::login($user);

        // Régénérer la session pour éviter la fixation de session.
        $request->session()->regenerate();


        // ======================================================
        // 4. REDIRECTION
        // ======================================================

        // L'inscription ne donne pas automatiquement accès
        // à une organisation existante.

        // Le rattachement à une organisation sera géré séparément.

        return redirect()->route('dashboard');
    }


}
