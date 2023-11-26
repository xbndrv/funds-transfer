FROM php:8.2-fpm

ENV APP_ENV="prod"

RUN apt-get update
RUN apt-get install -y libpq-dev libzip-dev zip
RUN apt-get install -y cron
RUN apt-get install -y git
RUN docker-php-ext-install pdo pdo_pgsql zip
RUN apt-get install -y libicu-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl

RUN curl -sS https://getcomposer.org/installer -o composer-setup.php
RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer
RUN rm -rf composer-setup.php

COPY ../../app /app

RUN composer install --no-interaction --no-dev

WORKDIR /app