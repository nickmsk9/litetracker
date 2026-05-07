FROM php:8.4-apache

RUN apt-get update && apt-get install -y \
    libmemcached-dev \
    zlib1g-dev \
    libzip-dev \
    unzip \
    git \
    logrotate \
    openssl \
    libjpeg62-turbo-dev \
    libpng-dev \
    libfreetype6-dev \
    && pecl install memcached \
    && docker-php-ext-enable memcached \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install mysqli gd \
    && echo "short_open_tag=On" > /usr/local/etc/php/conf.d/legacy.ini \
    && a2enmod rewrite ssl headers \
    && sed -ri '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
    && a2dissite 000-default \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY docker/apache/litetracker-http.conf /etc/apache2/sites-available/litetracker-http.conf
COPY docker/apache/litetracker-https.conf /etc/apache2/sites-available/litetracker-https.conf
COPY docker/apache/apache-start.sh /usr/local/bin/litetracker-apache-start
COPY docker/logrotate/litetracker /etc/logrotate.d/litetracker

RUN chmod +x /usr/local/bin/litetracker-apache-start \
    && chmod 644 /etc/logrotate.d/litetracker \
    && a2ensite litetracker-http litetracker-https

WORKDIR /var/www/html

CMD ["litetracker-apache-start"]
