
# ============================================================
# MESSENGERCUSTOM — DOCKERFILE DE PRODUCTION
# Stack : Laravel 13, Vue.js 3, Inertia.js, PostgreSQL
# Hébergement : Render
# ============================================================

# 1. IMAGE DE BASE
# PHP 8.5 avec le serveur web Apache intégré.
FROM php:8.5-apache

# 2. DÉPENDANCES SYSTÈME
# Installation des bibliothèques nécessaires à PHP,
# PostgreSQL, Composer et à la compilation du projet.
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    unzip \
    curl \
    git \
    && rm -rf /var/lib/apt/lists/*

# 3. INSTALLATION DE NODE.JS
# Node.js est nécessaire pour installer les dépendances
# JavaScript et compiler Vue.js avec Vite.
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get update \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# 4. EXTENSIONS PHP
# pdo_pgsql : connexion à PostgreSQL.
# mbstring : traitement des chaînes de caractères.
# xml : manipulation des documents XML.
# zip : prise en charge des archives ZIP.
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    mbstring \
    xml \
    zip

# 5. ACTIVATION DE MOD_REWRITE
# Apache utilise ce module pour transmettre les URL
# comme /login, /register et /up au routeur Laravel.
RUN a2enmod rewrite

# 6. CONFIGURATION DU DOCUMENTROOT APACHE
# Laravel doit exposer uniquement le dossier public.
# Les fichiers .env, app/, config/ et vendor/
# ne doivent pas être accessibles depuis le navigateur.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# Modification du DocumentRoot du VirtualHost Apache.
RUN sed -ri \
    's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf

# 7. AUTORISATION DU FICHIER .HTACCESS
# AllowOverride All permet à Apache de lire les règles
# présentes dans public/.htaccess.
# Require all granted autorise l'accès au dossier public.
RUN printf '%s\n' \
    '<Directory /var/www/html/public>' \
    '    Options FollowSymLinks' \
    '    AllowOverride All' \
    '    Require all granted' \
    '</Directory>' \
    > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

# 8. INSTALLATION DE COMPOSER
# Copie de Composer depuis son image officielle.
COPY --from=composer:latest \
    /usr/bin/composer /usr/bin/composer

# 9. RÉPERTOIRE DE TRAVAIL
# Toutes les commandes suivantes s'exécutent
# depuis le dossier principal du projet Laravel.
WORKDIR /var/www/html

# 10. COPIE DU CODE SOURCE
# Copie du projet dans l'image Docker.
# Le fichier public/.htaccess doit être inclus.
COPY . .

# 11. INSTALLATION DES DÉPENDANCES PHP
# --no-dev : exclut les dépendances de développement.
# --no-interaction : évite les questions interactives.
# --prefer-dist : privilégie les archives distribuées.
# --optimize-autoloader : optimise le chargement des classes.
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# 12. INSTALLATION DES DÉPENDANCES JAVASCRIPT
# npm ci utilise les versions du package-lock.json.
RUN npm ci

# 13. COMPILATION DES ASSETS DE PRODUCTION
# Compile Vue.js, Inertia.js et les styles avec Vite.
# Les fichiers compilés sont placés dans public/build.
RUN npm run build

# 14. SUPPRESSION DU MARQUEUR VITE DE DÉVELOPPEMENT
# public/hot ne doit pas être présent en production.
# Sinon Laravel peut chercher un serveur Vite local
# au lieu d'utiliser les fichiers compilés.
RUN rm -f public/hot

# 15. PERMISSIONS LARAVEL
# Apache doit pouvoir écrire dans storage/
# et bootstrap/cache/.
RUN mkdir -p \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    && chmod -R ug+rwX \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

# 16. VÉRIFICATION DE LA CONFIGURATION APACHE
# La construction Docker échoue si Apache détecte
# une erreur de syntaxe dans sa configuration.
RUN apache2ctl configtest

# 17. PORT HTTP
# Apache écoute sur le port 80 dans le conteneur.
EXPOSE 80

# 18. DÉMARRAGE DE L'APPLICATION
# Démarrage d'Apache au premier plan.
#
# IMPORTANT :
# Nous ne lançons PAS automatiquement les migrations.
# La base PostgreSQL de production contient déjà
# des tables et des données à préserver.
#
# Les migrations devront être exécutées séparément,
# après vérification du schéma de production.
CMD ["apache2-foreground"]
