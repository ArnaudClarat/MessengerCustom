<?php

use Tests\TestCase;

// ======================================================
// CONFIGURATION GLOBALE DES TESTS PEST
// ======================================================

// Les tests Feature utilisent l'application Laravel.
// Cela permet d'accéder aux routes, contrôleurs, modèles Eloquent et méthodes de test HTTP.
uses(TestCase::class)->in('Feature');