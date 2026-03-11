FROM php:8.1-cli-alpine

RUN apk add --no-cache \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    icu-dev oniguruma-dev libzip-dev \
    linux-headers \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
       pdo_mysql gd bcmath intl mbstring zip opcache pcntl sockets \
    && pecl install redis event \
    && docker-php-ext-enable redis \
    && rm -rf /var/cache/apk/* /tmp/pear

RUN echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www/xnapi
COPY . /var/www/xnapi

RUN mkdir -p runtime/logs runtime/views runtime/cache runtime/sessions runtime/yunos_logs \
    && chmod -R 777 runtime

EXPOSE 9588

CMD ["php", "start.php", "start"]
