<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Auth\OAuthController;



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
Route::middleware('auth')->group(function(){
    Route::get('/dashboard', function () {
        $user= auth()->user();

        return Inertia::render('Dashboard',[
            'user' => [
                'display_name' => $user->display_name,
                'email' => $user->email,
            ],
        ]);
    })->name('dashboard');

    Route::post('logout', [LoginController::class, 'logout'])
        ->name('logout');
});