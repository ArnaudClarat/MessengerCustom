<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\ConversationController;



Route::get('/', function () {
    return Inertia::render('Welcome', [
        'message' => 'MessengerCustom fonctionne avec Inertia + Vue + Tailwind !',
    ]);
});

// Authentification : routes accessibles sans connexion
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'show'])
        ->name('login');

    Route::post('login', [LoginController::class, 'login'])
        ->name('login.store');
});



// Connexion OAuth: Microsoft, Google et GitHub
Route::get('/auth/{provider}/redirect', [
    OAuthController::class,
    'redirect',
])->whereIn('provider', ['microsoft', 'google', 'github'])
    ->middleware('guest')
    ->name('oauth.redirect');

Route::get('/auth/{provider}/callback', [
    OAuthController::class,
    'callback',
])->whereIn('provider', ['microsoft', 'google', 'github'])
    ->name('oauth.callback');

// Liaison explicite d'un compte externe : utilisateur connecté obligatoire
Route::post('/auth/{provider}/link', [
    OAuthController::class,
    'link',
])->whereIn('provider', ['microsoft', 'google', 'github'])
    ->middleware('auth')
    ->name('oauth.link');



// Espace réservé aux utilisateurs connectés
//Route::middleware('auth')->group(function(){
//    Route::get('/dashboard', function () {
//        $user= auth()->user();
//
//        return Inertia::render('Dashboard',[
//            'user' => [
//                'display_name' => $user->display_name,
//                'email' => $user->email,
//            ],
//        ]);
//    })->name('dashboard');
//
//    Route::post('logout', [LoginController::class, 'logout'])
//        ->name('logout');
//});


// Espace réservé aux utilisateurs connectés
Route::middleware('auth')->group(function () {

    // Tableau de bord après connexion
    Route::get('/dashboard', function () {
        $user = auth()->user();

        return Inertia::render('Dashboard', [
            'user' => [
                'display_name' => $user->display_name,
                'email' => $user->email,
            ],
        ]);
    })->name('dashboard');

    // Historique des conversations de l'utilisateur connecté
    Route::get('/conversations', [
        ConversationController::class,
        'index'
    ])->name('conversations.index');

    // Envoi d'un message dans une conversation existante.
    // La route exige une authentification grâce au middleware 'auth'.
    // Le contrôleur vérifiera également que l'utilisateur appartient bien à la conversation avant l'enregistrement.
    Route::post('/conversations/{conversation}/messages', [
        ConversationController::class,
        'storeMessage'
    ])->name('conversations.messages.store');

    // Déconnexion
    Route::post('logout', [LoginController::class, 'logout'])
        ->name('logout');
});
