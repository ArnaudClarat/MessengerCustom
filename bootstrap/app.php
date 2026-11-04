
<?php

/**
 * ============================================================
 * MESSENGERCUSTOM — CONFIGURATION DE L'APPLICATION LARAVEL
 * ============================================================
 *
 * Ce fichier initialise l'application Laravel.
 *
 * Il permet de configurer :
 * 1. Les routes de l'application.
 * 2. Les middlewares HTTP.
 * 3. Les proxies de confiance (Render).
 * 4. La gestion des exceptions.
 *
 * Projet : Laravel 13 + Inertia.js + Vue.js
 * Hébergement : Render
 */

// Classe principale permettant de créer l'application Laravel.
use Illuminate\Foundation\Application;

// Classe utilisée pour configurer la gestion des exceptions.
use Illuminate\Foundation\Configuration\Exceptions;

// Classe permettant de configurer les middlewares.
use Illuminate\Foundation\Configuration\Middleware;

// Représente une requête HTTP.
// Utilisée notamment pour les en-têtes des proxies.
use Illuminate\Http\Request;

// Middleware personnalisé qui transmet les données
// partagées de Laravel aux composants Inertia/Vue.
use App\Http\Middleware\HandleInertiaRequests;


/**
 * ============================================================
 * 1. INITIALISATION DE L'APPLICATION
 * ============================================================
 *
 * dirname(__DIR__) indique le dossier racine du projet.
 *
 * Application::configure() prépare Laravel
 * avant le traitement des requêtes HTTP.
 */
return Application::configure(basePath: dirname(__DIR__))

    /**
     * ========================================================
     * 2. CONFIGURATION DES ROUTES
     * ========================================================
     */
    ->withRouting(

    // Routes HTTP de l'application :
    // accueil, inscription, connexion, dashboard, etc.
        web: __DIR__.'/../routes/web.php',

        // Commandes Artisan personnalisées.
        commands: __DIR__.'/../routes/console.php',

        // Autorisation des canaux de diffusion Laravel.
        // Utilisé notamment pour la messagerie temps réel.
        channels: __DIR__.'/../routes/channels.php',

        // Endpoint permettant de vérifier que Laravel fonctionne.
        // Exemple : https://messengercustom-69pa.onrender.com/up
        health: '/up',
    )

    /**
     * ========================================================
     * 3. CONFIGURATION DES MIDDLEWARES
     * ========================================================
     *
     * Un middleware intervient pendant le traitement
     * d'une requête HTTP.
     */
    ->withMiddleware(function (Middleware $middleware): void {

        /**
         * ----------------------------------------------------
         * 3.1. PROXY HTTPS DE RENDER
         * ----------------------------------------------------
         *
         * Render reçoit les connexions HTTPS puis transmet
         * les requêtes à notre application Laravel.
         *
         * Laravel doit reconnaître le protocole HTTPS
         * utilisé initialement par le navigateur.
         *
         * Sans cette configuration, Laravel peut générer
         * des redirections en HTTP :
         *
         * http://messengercustom-69pa.onrender.com/dashboard
         *
         * Le navigateur bloque alors ces redirections
         * depuis une page HTTPS (Mixed Content).
         *
         * ATTENTION :
         * at: '*' suppose que l'application est accessible
         * uniquement via un proxy de confiance qui contrôle
         * les en-têtes X-Forwarded-*.
         */

        $middleware->trustProxies(
            at: '*',

            headers: Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO
        );

        /**
         * ----------------------------------------------------
         * 3.2. MIDDLEWARE INERTIA.JS
         * ----------------------------------------------------
         *
         * HandleInertiaRequests permet notamment de :
         *
         * - Partager les informations de l'utilisateur.
         * - Transmettre les erreurs de validation.
         * - Partager les données communes avec Vue.js.
         *
         * Nous conservons ce middleware dans le groupe web.
         */
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);
    })

    /**
     * ========================================================
     * 4. GESTION DES EXCEPTIONS
     * ========================================================
     *
     * Les requêtes API et celles qui attendent explicitement
     * du JSON recevront des réponses d'erreur JSON.
     *
     * Les autres requêtes pourront utiliser le comportement
     * habituel de Laravel et Inertia.
     */
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) =>
                $request->is('api/*')
                || $request->expectsJson(),
        );
    })

    /**
     * ========================================================
     * 5. CRÉATION DE L'APPLICATION
     * ========================================================
     */
    ->create();
