FROM php:8.3-fpm-alpine
RUN apk add --no-cache git curl libzip-dev oniguruma-dev $PHPIZE_DEPS \
 && docker-php-ext-install pdo pdo_mysql mbstring zip bcmath
WORKDIR /var/www/html
