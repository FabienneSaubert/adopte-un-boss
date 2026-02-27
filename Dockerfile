################################################################################
# Stage 1 : installer les dépendances avec Composer
FROM composer:lts AS deps

WORKDIR /app

# Copier uniquement composer.json et composer.lock pour utiliser le cache
COPY composer.json composer.lock /app/

# Installer les dépendances sans dev
RUN composer install --no-dev --no-interaction --no-scripts

################################################################################
# Stage 2 : image finale PHP + Apache
FROM php:8.2-apache

WORKDIR /var/www/html

# Installer pdo_mysql pour Symfony
RUN docker-php-ext-install pdo_mysql

# Copier les dépendances depuis le stage Composer
COPY --from=deps /app/vendor/ ./vendor

# Copier le code source
COPY . /var/www/html

# Donner les droits à www-data
RUN chown -R www-data:www-data /var/www/html

# Activer le module Apache rewrite
RUN a2enmod rewrite

# Exposer le port Apache
EXPOSE 9000

# Lancer Apache au premier plan
CMD ["apache2-foreground"]