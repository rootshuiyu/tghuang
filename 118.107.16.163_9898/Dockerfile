FROM php:7.4-fpm-alpine

RUN apk add --no-cache \
    nginx supervisor \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    icu-dev oniguruma-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
       pdo_mysql mysqli gd bcmath intl mbstring zip opcache pcntl \
    && rm -rf /var/cache/apk/*

RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "upload_max_filesize=20M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size=20M" >> /usr/local/etc/php/conf.d/uploads.ini

COPY docker/nginx-admin.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord-admin.conf /etc/supervisor.d/admin.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . /var/www/html

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && mkdir -p runtime/cache runtime/temp runtime/log runtime/yunos_logs public/uploads \
    && chown -R www-data:www-data runtime public/uploads \
    && chmod -R 775 runtime

EXPOSE 9898

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisord.conf"]
