# On part d'une image officielle PHP 8.2 avec Apache
FROM php:8.5-apache

# 1. Installation des dépendances système (nécessaires pour Postgres, Composer et Node)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    libonig-dev \
    unzip \
    curl \
    git \
    && rm -rf /var/lib/apt/lists/*

# 2. Installation de Node.js (indispensable pour compiler Vue/React avec Vite)
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get update \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# 3. Installation des extensions PHP pour Laravel et PostgreSQL (Neon)
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    mbstring \
    xml \
    zip

# 4. Activation de l'URL Rewriting (obligatoire pour le routeur Laravel)
RUN a2enmod rewrite

# 5. On indique à Apache que la racine du site est le dossier "public" de Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 6. Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 7. Copie de tout le code de ton projet dans le conteneur
WORKDIR /var/www/html

# Dépendances PHP
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# Dépendances frontend
COPY package.json package-lock.json ./
RUN npm ci

# Code de l'application
COPY . .

# Compilation Vite
RUN npm run build

# Permissions Laravel
RUN chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

EXPOSE 80

# Render exécutera les migrations au démarrage,
# puis lancera Apache.
CMD ["sh", "-c", "php artisan migrate --force && apache2-foreground"]