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

COPY package*.json ./
RUN npm install

COPY webpack.mix.js ./
COPY resources ./resources
COPY beike/Installer/assets ./beike/Installer/assets

RUN mkdir -p /app/public \
 && ln -s /app/public /public \
 && npm run prod \
 && MANIFEST_PATH="$(find /app /public -maxdepth 3 -name mix-manifest.json | head -n 1)" \
 && test -n "$MANIFEST_PATH" \
 && cp "$MANIFEST_PATH" /app/public/mix-manifest.json

FROM php:8.3-cli

WORKDIR /app

RUN apt-get update && apt-get install -y \
    git \
    libcurl4-openssl-dev \
    libfreetype6-dev \
    libicu-dev \
    libjpeg62-turbo-dev \
    libonig-dev \
    libpq-dev \
    libpng-dev \
    libsqlite3-dev \
    libxml2-dev \
    libzip-dev \
    unzip \
    zip \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install \
    bcmath \
    curl \
    gd \
    intl \
    mbstring \
    pcntl \
    pgsql \
    pdo_pgsql \
    pdo_sqlite \
    simplexml \
    zip \
  && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build
COPY --from=assets /app/public/install ./public/install
COPY --from=assets /app/public/mix-manifest.json ./public/mix-manifest.json

RUN rm -f bootstrap/cache/*.php \
  && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
  && chown -R www-data:www-data /app

EXPOSE 10000

CMD ["bash", "scripts/render-start.sh"]
