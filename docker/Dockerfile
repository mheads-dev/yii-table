FROM composer/composer:latest-bin AS composer

FROM php:8.3-cli-bookworm

# System packages

RUN apt-get update && apt-get install -y \
    unzip \
    zlib1g-dev \
    libzip-dev

# PHP extensions

RUN docker-php-ext-install \
    pdo_mysql \
    # For Psalm, to make use of JIT for a 20%+ performance boost. \
    opcache

# For code coverage (mutation testing)
RUN pecl install pcov && docker-php-ext-enable pcov

# For Excel export via Vtiful/Kernel (ext-xlswriter)
RUN pecl install xlswriter && docker-php-ext-enable xlswriter

# Composer

COPY --from=composer /composer /usr/bin/composer

# Code

COPY . /code
RUN rm -f /code/composer.lock
WORKDIR /code

# PHP packages

RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-cache
