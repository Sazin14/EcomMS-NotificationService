# ---------- Frontend assets ----------
FROM node:22-alpine AS frontend-build
WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build


# ---------- PHP dependencies ----------
FROM php:8.4-cli-alpine AS composer-build

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apk add --no-cache git \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        linux-headers \
    && docker-php-ext-install sockets \
    && apk del .build-deps

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-install=dist \
    --optimize-autoloader \
    --no-scripts

COPY . .
RUN composer dump-autoload \
    --no-dev \
    --optimize \
    --no-scripts


# ---------- Runtime ----------
FROM php:8.4-fpm-alpine AS runtime

RUN apk add --no-cache \
        nginx \
        supervisor \
        icu-libs \
        libzip \
        sqlite-libs \
        libpq \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        linux-headers \
        icu-dev \
        libzip-dev \
        sqlite-dev \
        postgresql-dev \
    && docker-php-ext-install \
        sockets \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
        bcmath \
        intl \
        opcache \
        zip \
    && apk del .build-deps

WORKDIR /var/www/html

COPY --from=composer-build /app /var/www/html
COPY --from=frontend-build /app/public/build /var/www/html/public/build

RUN mkdir -p \
        /run/nginx \
        /var/log/supervisor \
        /var/www/html/storage/framework/cache/data \
        /var/www/html/storage/framework/sessions \
        /var/www/html/storage/framework/views \
        /var/www/html/bootstrap/cache \
        /var/www/html/database \
    && chown -R www-data:www-data \
        /var/www/html/storage \
        /var/www/html/bootstrap/cache \
        /var/www/html/database

COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["api"]
