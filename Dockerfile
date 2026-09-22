# On part d'une image officielle PHP 8.2 avec Apache
FROM php:8.2-apache

# 1. Installation des dépendances système (nécessaires pour Postgres, Composer et Node)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    curl \
    git

# 2. Installation de Node.js (indispensable pour compiler Vue/React avec Vite)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# 3. Installation des extensions PHP pour Laravel et PostgreSQL (Neon)
RUN docker-php-ext-install pdo pdo_pgsql

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
COPY . .

# --- ATTENTION --- 
# Tant que tu n'as pas généré le vrai projet Laravel (avec composer.json et package.json), 
# laisse les 3 lignes ci-dessous COMMENTÉES pour que ton "Hello World" basique puisse se déployer.
# Dès que tu auras ton vrai projet, DÉCOMMENTE-LES.

# RUN composer install --optimize-autoloader --no-dev
# RUN npm install && npm run build
# RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache