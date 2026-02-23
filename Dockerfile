FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    git \
    unzip \
    libxml2-dev \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    mysql-client

RUN docker-php-ext-install \
    intl \
    pdo_mysql \
    zip \
    bcmath \
    opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-interaction --optimize-autoloader

EXPOSE 9000
CMD ["php-fpm"]
