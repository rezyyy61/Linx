FROM php:8.3-cli-alpine

RUN apk add --no-cache \
    bash git curl unzip openssl \
    icu-libs libzip oniguruma \
    ffmpeg imagemagick ghostscript poppler-utils \
    libwebp-tools \
    perl-image-exiftool tiff jpeg libpng librsvg \
    ttf-dejavu

RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    icu-dev libzip-dev oniguruma-dev zlib-dev linux-headers

# PHP extensions
RUN docker-php-ext-install -j"$(nproc)" \
    pdo_mysql mbstring bcmath intl zip sockets pcntl

# Redis ext
RUN pecl install redis \
  && docker-php-ext-enable redis

# Clean up build deps
RUN apk del .build-deps

WORKDIR /var/www/html
