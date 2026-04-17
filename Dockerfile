FROM php:8.4-apache

RUN apt-get update && apt-get install -y \
    libmemcached-dev \
    zlib1g-dev \
    libzip-dev \
    unzip \
    git \
    libjpeg62-turbo-dev \
    libpng-dev \
    libfreetype6-dev \
    && pecl install memcached \
    && docker-php-ext-enable memcached \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install mysqli gd \
    && echo "short_open_tag=On" > /usr/local/etc/php/conf.d/legacy.ini \
    && a2enmod rewrite \
    && sed -ri '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html