FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
        git \
        unzip \
        libicu-dev \
        libonig-dev \
        libzip-dev \
        zip \
        curl \
    && docker-php-ext-install intl pdo pdo_mysql zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN php -d memory_limit=-1 /usr/bin/composer install --no-interaction --optimize-autoloader --no-scripts

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]