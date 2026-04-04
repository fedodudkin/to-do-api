FROM php:8.2-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
    libsqlite3-dev \
    git \
    unzip \
    && docker-php-ext-install pdo pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json ./
RUN composer install --no-interaction --optimize-autoloader

COPY . .

RUN cp docker/entrypoint.sh /usr/local/bin/app-entrypoint.sh \
    && chmod +x /usr/local/bin/app-entrypoint.sh \
    && mkdir -p database logs \
    && chown -R www-data:www-data database logs

ENTRYPOINT ["/usr/local/bin/app-entrypoint.sh"]
CMD ["php-fpm"]
