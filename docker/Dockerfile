ARG PHP_VERSION=8.2

FROM php:${PHP_VERSION}-cli-alpine

RUN apk add --no-cache linux-headers autoconf build-base git zip libzip-dev

RUN docker-php-ext-install zip

RUN pecl install opentelemetry xdebug \
    && docker-php-ext-enable opentelemetry xdebug

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
