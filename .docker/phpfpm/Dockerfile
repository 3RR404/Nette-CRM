FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    mysql-client \
    unzip \
    p7zip \
    curl \
    git \
    nodejs \
    npm \
    dcron \
    $PHPIZE_DEPS

RUN docker-php-ext-configure gd --with-freetype --with-jpeg

RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    zip \
    intl \
    gd \
    opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY php.ini "$PHP_INI_DIR/conf.d/app.ini"

WORKDIR /var/www/html
