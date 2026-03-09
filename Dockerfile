FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts \
    --ignore-platform-req=php \
    --ignore-platform-req=ext-pcntl \
    --ignore-platform-req=ext-gd

FROM node:20 AS assets

WORKDIR /app

COPY package.json ./
RUN npm install

COPY resources ./resources
COPY beike ./beike
COPY webpack.mix.js ./
COPY public ./public
RUN npm run prod

FROM php:8.3-cli

WORKDIR /app

RUN apt-get update && apt-get install -y \
    git \
    libcurl4-openssl-dev \
    libicu-dev \
    libonig-dev \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    unzip \
    zip \
  && docker-php-ext-configure gd \
  && docker-php-ext-install \
    bcmath \
    curl \
    gd \
    intl \
    mbstring \
    pcntl \
    pdo_sqlite \
    simplexml \
    zip \
  && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build
COPY --from=assets /app/public/mix-manifest.json ./public/mix-manifest.json

RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
  && chown -R www-data:www-data /app

EXPOSE 10000

CMD ["bash", "scripts/render-start.sh"]
