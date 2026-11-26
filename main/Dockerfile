# Stage 1: Compile Vite frontend
FROM --platform=$BUILDPLATFORM node:22-bookworm-slim AS frontend

WORKDIR /build

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build


# Stage 2: Laravel application
FROM php:8.4-apache

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libicu-dev \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd \
       --with-freetype --with-jpeg \
    && docker-php-ext-install \
       pdo_mysql bcmath intl zip gd exif \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN a2enmod rewrite headers \
    && sed -ri \
       's!/var/www/html!/var/www/html/public!g' \
       /etc/apache2/sites-available/000-default.conf \
    && printf 'upload_max_filesize=12M\npost_max_size=14M\n' \
       > /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html

COPY . .

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

COPY --from=frontend /build/public/build ./public/build

RUN mkdir -p \
      storage/framework/cache \
      storage/framework/sessions \
      storage/framework/views \
      storage/logs \
      bootstrap/cache \
    && chown -R www-data:www-data \
      storage bootstrap/cache

EXPOSE 80

CMD ["apache2-foreground"]
