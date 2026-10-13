<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Charger les dépendances de Composer.
require __DIR__.'/../vendor/autoload.php';

// Démarrer l'application Laravel.
$app = require_once __DIR__.'/../bootstrap/app.php';

// Transmettre la requête HTTP à Laravel.
$app->handleRequest(Request::capture());
