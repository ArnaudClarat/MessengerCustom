<?php

// Gestion de la connexion et de la déconnexion.
use App\Http\Controllers\Auth\LoginController;

// Gestion de l'inscription des nouveaux utilisateurs.
use App\Http\Controllers\Auth\RegisterController;

// Authentification OAuth : Microsoft, Google et GitHub.
use App\Http\Controllers\Auth\OAuthController;

// Gestion des conversations et des messages.
use App\Http\Controllers\ConversationController;

// Déclaration des routes HTTP Laravel.
use Illuminate\Support\Facades\Route;

// Affichage des composants Vue.js via Inertia.
use Inertia\Inertia;


// ============================================================
// 1. PAGE D'ACCUEIL PUBLIQUE
// ============================================================

// Accessible à tous, même sans connexion.
Route::get('/', function () {

    return Inertia::render('Landing');

})->name('landing');


// ============================================================
// 2. AUTHENTIFICATION CLASSIQUE : CONNEXION ET INSCRIPTION
// ============================================================

// Le middleware 'guest' réserve ces routes aux visiteurs qui ne sont pas encore authentifiés.
// Un utilisateur déjà connecté ne doit pas pouvoir ouvrir une nouvelle session d'inscription depuis ce groupe.

Route::middleware('guest')->group(function () {

    // --------------------------------------------------------
    // 2.1. AFFICHER LE FORMULAIRE DE CONNEXION
    // --------------------------------------------------------

    // GET /login
    // LoginController@show affiche Auth/Login.vue.

    Route::get('/login', [
        LoginController::class,
        'show',
    ])->name('login');


    // --------------------------------------------------------
    // 2.2. TRAITER LA CONNEXION
    // --------------------------------------------------------

    // POST /login
    // Laravel valide les identifiants et ouvre la session.

    Route::post('/login', [
        LoginController::class,
        'login',
    ])->name('login.store');


    // --------------------------------------------------------
    // 2.3. AFFICHER LE FORMULAIRE D'INSCRIPTION
    // --------------------------------------------------------

    // GET /register
    // RegisterController@show affiche Auth/Register.vue.
    // Cette page permet de saisir le nom complet, l'adresse e-mail, le mot de passe, la confirmation du mot de passe.

    Route::get('/register', [
        RegisterController::class,
        'show',
    ])->name('register');


    // --------------------------------------------------------
    // 2.4. ENREGISTRER UN NOUVEL UTILISATEUR
    // --------------------------------------------------------

    // POST /register

    // RegisterController@store doit :
    // 1. Valider les données reçues.
    // 2. Vérifier l'unicité de l'adresse e-mail.
    // 3. Hacher le mot de passe.
    // 4. Créer l'utilisateur dans la base de données.
    // 5. Authentifier le nouvel utilisateur.
    // 6. Régénérer la session.
    // 7. Rediriger vers le Dashboard.

    // IMPORTANT :
    // L'inscription ne doit pas attribuer automatiquement une appartenance à une organisation existante.

    Route::post('/register', [
        RegisterController::class,
        'store',
    ])->name('register.store');

});


// ============================================================
// 3. AUTHENTIFICATION OAUTH
// ============================================================

// Connexion via Microsoft, Google ou GitHub.
// Le paramètre {provider} est limité aux trois fournisseurs autorisés grâce à whereIn().
// Non implémenter => ne fonctionne pas!!!

// ------------------------------------------------------------
// 3.1. REDIRECTION VERS LE FOURNISSEUR OAUTH
// ------------------------------------------------------------

// GET /auth/{provider}/redirect
// Exemple : /auth/google/redirect
// Le middleware 'guest' exige que le visiteur soit déconnecté.

Route::get('/auth/{provider}/redirect', [
    OAuthController::class,
    'redirect',
])
    ->whereIn('provider', ['microsoft', 'google', 'github'])
    ->middleware('guest')
    ->name('oauth.redirect');


// ------------------------------------------------------------
// 3.2. RETOUR DU FOURNISSEUR OAUTH
// ------------------------------------------------------------

// GET /auth/{provider}/callback
// Le fournisseur renvoie l'utilisateur vers cette URL. OAuthController vérifie la réponse et gère la session.

Route::get('/auth/{provider}/callback', [
    OAuthController::class,
    'callback',
])
    ->whereIn('provider', ['microsoft', 'google', 'github'])
    ->name('oauth.callback');


// ------------------------------------------------------------
// 3.3. ASSOCIATION D'UN COMPTE OAUTH
// ------------------------------------------------------------

// POST /auth/{provider}/link
// Permet à un utilisateur connecté d'associer un fournisseur OAuth supplémentaire à son compte MessengerCustom.
// Le middleware 'auth' impose une connexion préalable.

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

// Toutes les routes de ce groupe nécessitent une session utilisateur valide.
// Le middleware 'auth' empêche les visiteurs non connectés d'accéder aux conversations et aux messages.

Route::middleware('auth')->group(function () {

    // --------------------------------------------------------
    // 4.1. TABLEAU DE BORD
    // --------------------------------------------------------

    // GET /dashboard
    // Affiche les informations de l'utilisateur connecté dans resources/js/Pages/Dashboard.vue.

    Route::get('/dashboard', function () {

        // Récupérer l'utilisateur actuellement authentifié.
        $user = auth()->user();

        // Transmettre les informations nécessaires à Vue.js.
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

    // GET /conversations
    // ConversationController@index récupère les conversations auxquelles appartient l'utilisateur connecté.
    // Les données sont ensuite transmises au composant Vue.js.

    Route::get('/conversations', [
        ConversationController::class,
        'index',
    ])->name('conversations.index');


    // --------------------------------------------------------
    // 4.3. CRÉATION D'UNE CONVERSATION INDIVIDUELLE
    // --------------------------------------------------------

    // POST /conversations
    // ConversationController@store :
    // - valide le destinataire ;
    // - vérifie l'organisation commune ;
    // - empêche la création de doublons ;
    // - enregistre la conversation et ses membres.

    Route::post('/conversations', [
        ConversationController::class,
        'store',
    ])->name('conversations.store');


    // --------------------------------------------------------
    // 4.4. CRÉATION D'UNE DISCUSSION DE GROUPE
    // --------------------------------------------------------

    // POST /conversations/groups
    // ConversationController@storeGroup :
    // - valide le nom du groupe ;
    // - exige au moins deux participants invités ;
    // - vérifie les appartenances à une organisation commune ;
    // - crée la conversation de type 'group' ;
    // - ajoute le créateur et les participants au groupe.

    Route::post('/conversations/groups', [
        ConversationController::class,
        'storeGroup',
    ])->name('conversations.groups.store');


    // --------------------------------------------------------
    // 4.5. ENVOI D'UN MESSAGE
    // --------------------------------------------------------

    // POST /conversations/{conversation}/messages
    // {conversation} représente l'UUID de la conversation.
    // ConversationController@storeMessage :
    // - vérifie que l'utilisateur appartient à la conversation ;
    // - valide le contenu du message ;
    // - enregistre le message ;
    // - déclenche l'événement MessageSent.
    // Laravel Reverb permet ensuite sa diffusion en temps réel.

    Route::post('/conversations/{conversation}/messages', [
        ConversationController::class,
        'storeMessage',
    ])->name('conversations.messages.store');


    // --------------------------------------------------------
    // 4.6. DÉCONNEXION
    // --------------------------------------------------------

    // POST /logout

    // LoginController@logout :
    // - ferme la session authentifiée ;
    // - invalide l'ancienne session ;
    // - régénère le jeton CSRF ;
    // - redirige vers la page de connexion.

    Route::post('/logout', [
        LoginController::class,
        'logout',
    ])->name('logout');

});
