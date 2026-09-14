# Base image with PHP + the extensions Laravel needs.
FROM dunglas/frankenphp:1-php8.5 AS base
RUN install-php-extensions pdo_sqlite sqlite3 intl zip opcache

# Build stage: has Node + Composer so it can install dependencies and run
# `npm run build` (which shells out to `php artisan wayfinder:generate`,
# hence needing PHP + vendor/ + the app source already in place).
FROM base AS build
WORKDIR /app

RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-dev --no-interaction --optimize-autoloader
RUN npm ci && npm run build && rm -rf node_modules

# Runtime stage: only the built application, no Node/Composer/build tools.
FROM base AS runtime
WORKDIR /app

COPY --from=build /app /app
COPY Caddyfile /etc/caddy/Caddyfile

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    CACHE_STORE=database \
    SESSION_DRIVER=database \
    QUEUE_CONNECTION=sync

RUN chown -R www-data:www-data storage bootstrap/cache database

EXPOSE 80
