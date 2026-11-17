<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
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
        // 2bis. RATTACHEMENT À UNE ORGANISATION PAR DÉFAUT
        // ======================================================
        //
        // CORRECTIF (bug production) :
        // En développement, la base est alimentée par des seeders
        // dont les utilisateurs partagent déjà une organisation.
        // La création de conversations (directes et de groupe) exige,
        // dans ConversationController, qu'il existe une organisation
        // commune ACTIVE entre les participants (table org_members).
        //
        // En production, les comptes réellement créés via ce formulaire
        // n'étaient rattachés à AUCUNE organisation. La recherche
        // d'organisation commune ($orgId) renvoyait donc toujours null,
        // ce qui rejetait systématiquement :
        //   - la création de discussions directes ;
        //   - la création de groupes ;
        //   - l'ajout d'un utilisateur à un groupe.
        //
        // On rattache désormais chaque nouvel inscrit à une organisation
        // par défaut partagée (comportement identique à celui du dev).
        // Ainsi, tous les utilisateurs inscrits appartiennent à la même
        // organisation active et peuvent discuter entre eux.
        DB::transaction(function () use ($user) {

            // Récupérer l'organisation par défaut, ou la créer si elle
            // n'existe pas encore (premier inscrit en production).
            // Le slug est unique : on s'appuie dessus pour éviter les doublons.
            $orgId = DB::table('organizations')
                ->where('slug', 'default')
                ->value('id');

            if ($orgId === null) {
                $orgId = (string) Str::uuid();

                DB::table('organizations')->insert([
                    'id' => $orgId,
                    'name' => 'MessengerCustom',
                    'slug' => 'default',
                    'created_at' => now(),
                ]);
            }

            // Ajouter l'utilisateur comme membre ACTIF de cette organisation.
            // insertOrIgnore évite une erreur si l'appartenance existe déjà
            // (clé primaire composée org_id + user_id).
            DB::table('org_members')->insertOrIgnore([
                'org_id' => $orgId,
                'user_id' => $user->id,
                'role' => 'member',
                'status' => 'active',
                'joined_at' => now(),
            ]);
        });


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

        // L'utilisateur est désormais rattaché à l'organisation par défaut
        // (voir étape 2bis), il peut donc créer des conversations et des
        // groupes immédiatement après son inscription.

        return redirect()->route('dashboard');
    }


}
