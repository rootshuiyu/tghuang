FROM php:8.2-cli-alpine

RUN apk add --no-cache \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    icu-dev oniguruma-dev libzip-dev \
    linux-headers libevent-dev openssl-dev unzip \
    && apk add --no-cache --virtual .build-deps \
       $PHPIZE_DEPS autoconf gcc g++ make \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
       pdo_mysql gd bcmath intl mbstring zip opcache pcntl sockets \
    && pecl install redis \
    && pecl install event \
    && docker-php-ext-enable redis \
    && docker-php-ext-enable --ini-name zz-event.ini event \
    && apk del .build-deps \
    && rm -rf /var/cache/apk/* /tmp/pear

RUN echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/xnapi
COPY . /var/www/xnapi

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && mkdir -p runtime/logs runtime/views runtime/cache runtime/sessions runtime/yunos_logs \
    && chmod -R 777 runtime

EXPOSE 9588

CMD ["php", "start.php", "start"]
