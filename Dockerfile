ARG LARAVEL_PATH=/var/www/html

FROM composer:1.9.3 AS composer
ARG LARAVEL_PATH

COPY src/composer.json $LARAVEL_PATH
COPY src/composer.lock $LARAVEL_PATH
RUN composer install --working-dir $LARAVEL_PATH --ignore-platform-reqs --no-progress --no-autoloader --no-scripts

COPY src $LARAVEL_PATH
RUN composer install --working-dir $LARAVEL_PATH --ignore-platform-reqs --no-progress --optimize-autoloader

FROM php:7.4-apache
ARG LARAVEL_PATH

RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libmcrypt-dev \
        libpng-dev \
        zlib1g-dev \
        libxml2-dev \
        libzip-dev \
        libonig-dev \
        graphviz \
        libcurl4-openssl-dev \
        pkg-config \
        libssl-dev \
        supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install mysqli \
    && docker-php-ext-install exif \
    && docker-php-ext-install zip \
    && docker-php-ext-install pdo \
    && docker-php-ext-install mbstring \
    && docker-php-source delete

ENV APACHE_DOCUMENT_ROOT $LARAVEL_PATH/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN a2enmod rewrite

COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN mkdir -p /root/.composer
COPY --from=composer /tmp/cache /root/.composer/cache

RUN pecl install xdebug-2.9.2 \
	&& docker-php-ext-enable xdebug \
    && echo "xdebug.remote_enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

COPY --from=composer $LARAVEL_PATH $LARAVEL_PATH

WORKDIR $LARAVEL_PATH

RUN useradd -G www-data,root -u 1000 -d /home/devuser devuser
RUN mkdir -p /home/devuser/.composer && \
    chown -R devuser:devuser /home/devuser

# Permissions
RUN chgrp -R www-data storage public $LARAVEL_PATH/bootstrap/cache
RUN chmod -R ug+rwx storage public $LARAVEL_PATH/bootstrap/cache

RUN php artisan config:cache
RUN php artisan config:clear