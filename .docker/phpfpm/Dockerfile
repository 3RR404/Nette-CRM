FROM php:8.4-fpm-alpine

COPY --chown=www-data:www-data ./ /app

COPY ./.docker/phpfpm/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY ./.docker/phpfpm/docker-fpm-entrypoint /usr/local/bin/

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

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY ./.docker/phpfpm/php.ini "$PHP_INI_DIR/conf.d/app.ini"

ENTRYPOINT ["docker-fpm-entrypoint"]

WORKDIR /app

CMD ["php-fpm"]
