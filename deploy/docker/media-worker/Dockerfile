FROM php:8.3-cli-alpine

# ابزارهای پایه + ویدیو/پی‌دی‌اف/تصویر
RUN apk add --no-cache \
    bash git curl unzip openssl \
    icu-libs libzip oniguruma \
    ffmpeg imagemagick ghostscript poppler-utils \
    libwebp-tools \
    perl-image-exiftool tiff jpeg libpng librsvg \
    fontconfig ttf-dejavu font-liberation \
    libreoffice \
    font-noto font-noto-cjk font-noto-emoji

RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    icu-dev libzip-dev oniguruma-dev zlib-dev linux-headers

RUN docker-php-ext-install -j"$(nproc)" \
    pdo_mysql mbstring bcmath intl zip sockets pcntl

RUN pecl install redis && docker-php-ext-enable redis

# کش فونت‌ها (اختیاری اما مفید برای رندر)
RUN fc-cache -f

# چک ابزارها (اختیاری)
RUN soffice --version && pdfinfo -v && pdftoppm -v

RUN apk del .build-deps

WORKDIR /var/www/html
