ARG PHP_VERSION=7.3.19-fpm

# base image
FROM php:${PHP_VERSION} as base

WORKDIR /var/www

RUN apt-get update \
    && apt-get install -y zip unzip libpq-dev \
    && pecl install xdebug-2.8.1 \
    && docker-php-ext-enable xdebug

RUN mkdir /root/.composer/

RUN curl https://getcomposer.org/composer-stable.phar -o /usr/bin/composer && chmod +x /usr/bin/composer

CMD composer install ; php-fpm