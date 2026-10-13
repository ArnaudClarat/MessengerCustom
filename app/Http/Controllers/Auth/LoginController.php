<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class LoginController extends Controller
{
    /**
     * Affiche le formulaire de connexion
     */
    public function show(): Response
    {
        return Inertia::render('Auth/Login');
    }


    /**
     * Vérifie les identifiants et connecte l'utilisateur.
     */

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if(!Auth::attempt($credentials)){
            throw ValidationException::withMessages([
                'email' => 'Les identifiants fournis sont incorrects.'
            ]);
        }

        // Renouvelle l'identifiant de session après la connexion.
        $request->session()->regenerate();

        return redirect()->route('dashboard');

    }


    /**
     * Déconnecte l'utilisateur et invalide sa session.
    */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

}
