# PHP-FPM for Laravel (dev/prod base)
FROM php:8.3-fpm-alpine

# System deps
RUN apk add --no-cache \
  bash git curl unzip openssl \
  icu-dev libzip-dev oniguruma-dev \
  $PHPIZE_DEPS

# PHP extensions needed by Laravel
RUN docker-php-ext-install pdo_mysql mbstring bcmath intl zip opcache

# PHP Redis extension
RUN pecl install redis \
  && docker-php-ext-enable redis

# Install Composer (from official image)
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# PHP config
COPY deploy/docker/php/conf/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY deploy/docker/php/conf/opcache.ini /usr/local/etc/php/conf.d/10-opcache.ini

# Entrypoint
COPY deploy/docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm", "-F"]
