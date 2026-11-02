<?php

// ============================================================
// IMPORTATION DES CONTRÔLEURS ET DES FACADES
// ============================================================

// Contrôleur responsable de la connexion et de la déconnexion.
use App\Http\Controllers\Auth\LoginController;

// Contrôleur responsable de l'authentification OAuth avec Microsoft, Google et GitHub.
use App\Http\Controllers\Auth\OAuthController;

// Contrôleur responsable de la gestion des conversations et de l'envoi des messages.
use App\Http\Controllers\ConversationController;

// Facade Laravel permettant de déclarer les routes HTTP.
use Illuminate\Support\Facades\Route;

// Inertia permet d'afficher les composants Vue.js depuis les routes et contrôleurs Laravel.
use Inertia\Inertia;


// ============================================================
// 1. PAGE D'ACCUEIL PUBLIQUE
// ============================================================

// Cette route est accessible sans authentification.
// Elle affiche le composant Vue.js : resources/js/Pages/Landing.vue

Route::get('/', function () {
    return Inertia::render('Landing');
})->name('landing');


// ============================================================
// 2. AUTHENTIFICATION CLASSIQUE
// ============================================================

// Le middleware 'guest' autorise uniquement les utilisateurs qui ne sont pas connectés.

Route::middleware('guest')->group(function () {

    // Afficher le formulaire de connexion.
    // Méthode HTTP : GET
    // URL : /login
    Route::get('/login', [
        LoginController::class,
        'show',
    ])->name('login');

    // Traiter les informations envoyées par le formulaire.
    // Méthode HTTP : POST
    // URL : /login
    Route::post('/login', [
        LoginController::class,
        'login',
    ])->name('login.store');

});


// ============================================================
// 3. AUTHENTIFICATION OAUTH
// ============================================================

// Ces routes permettent l'authentification avec Microsoft, Google ou GitHub.

// Rediriger l'utilisateur vers le fournisseur OAuth.
// Exemple : /auth/google/redirect
// Seuls les visiteurs non connectés peuvent utiliser cette route.

Route::get('/auth/{provider}/redirect', [
    OAuthController::class,
    'redirect',
])
    ->whereIn('provider', ['microsoft', 'google', 'github'])
    ->middleware('guest')
    ->name('oauth.redirect');


// Recevoir la réponse du fournisseur OAuth.
// Exemple : /auth/google/callback
// Le contrôleur vérifie la réponse du fournisseur
// et établit la session utilisateur si elle est valide.

Route::get('/auth/{provider}/callback', [
    OAuthController::class,
    'callback',
])
    ->whereIn('provider', ['microsoft', 'google', 'github'])
    ->name('oauth.callback');


// Permettre à un utilisateur déjà connecté
// d'associer un fournisseur OAuth à son compte.
// Cette opération nécessite une authentification.

Route::post('/auth/{provider}/link', [
    OAuthController::class,
    'link',
])
    ->whereIn('provider', ['microsoft', 'google', 'github'])
    ->middleware('auth')
    ->name('oauth.link');


// ============================================================
// 4. ESPACE PROTÉGÉ : UTILISATEURS AUTHENTIFIÉS
// ============================================================

// Toutes les routes déclarées dans ce groupe
// nécessitent une session utilisateur valide.
//
// Si l'utilisateur n'est pas authentifié,
// Laravel lui refuse l'accès et peut le rediriger
// vers la page de connexion.

Route::middleware('auth')->group(function () {

    // --------------------------------------------------------
    // 4.1. TABLEAU DE BORD
    // --------------------------------------------------------

    // Afficher le tableau de bord après connexion.
    // URL : GET /dashboard

    Route::get('/dashboard', function () {

        // Récupérer l'utilisateur actuellement connecté.
        $user = auth()->user();

        // Transmettre les informations de l'utilisateur
        // au composant Vue.js Dashboard.vue.
        return Inertia::render('Dashboard', [
            'user' => [
                'display_name' => $user->display_name,
                'email' => $user->email,
            ],
        ]);

    })->name('dashboard');


    // --------------------------------------------------------
    // 4.2. AFFICHAGE DES CONVERSATIONS
    // --------------------------------------------------------

    // Afficher les conversations de l'utilisateur connecté.
    //
    // Le contrôleur récupère les conversations
    // depuis PostgreSQL avec Eloquent.
    //
    // URL : GET /conversations

    Route::get('/conversations', [
        ConversationController::class,
        'index',
    ])->name('conversations.index');


    // --------------------------------------------------------
    // 4.3. CRÉATION D'UNE CONVERSATION
    // --------------------------------------------------------

    // Créer une nouvelle conversation.
    //
    // Le contrôleur devra :
    // - valider les données reçues ;
    // - vérifier les participants ;
    // - vérifier leur organisation commune ;
    // - éviter les conversations individuelles en double ;
    // - enregistrer la conversation et ses participants.
    //
    // URL : POST /conversations

    Route::post('/conversations', [
        ConversationController::class,
        'store',
    ])->name('conversations.store');


    // --------------------------------------------------------
    // 4.4. ENVOI D'UN MESSAGE
    // --------------------------------------------------------

    // Enregistrer un message dans une conversation existante.
    // Le paramètre {conversation} contient l'UUID de la conversation concernée.

    // Le contrôleur vérifie que l'utilisateur connecté appartient bien à cette conversation.

    // Après enregistrement, l'événement MessageSent diffuse le message via Laravel Reverb.
    // URL : POST /conversations/{conversation}/messages

    Route::post('/conversations/{conversation}/messages', [
        ConversationController::class,
        'storeMessage',
    ])->name('conversations.messages.store');


    // --------------------------------------------------------
    // 4.5. DÉCONNEXION
    // --------------------------------------------------------

    // Fermer la session de l'utilisateur connecté.
    // La déconnexion utilise POST afin de bénéficier de la protection CSRF de Laravel.

    // URL : POST /logout

    Route::post('/logout', [
        LoginController::class,
        'logout',
    ])->name('logout');

});
