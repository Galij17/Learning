FROM dunglas/frankenphp:1-php8.3

WORKDIR /app

# System dependencies + Node.js 22 for Vite asset compilation
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    git \
    unzip \
    ca-certificates \
    gnupg \
    && mkdir -p /etc/apt/keyrings \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_22.x nodistro main" > /etc/apt/sources.list.d/nodesource.list \
    && apt-get update && apt-get install -y --no-install-recommends nodejs \
    && install-php-extensions \
        pdo_mysql \
        mbstring \
        xml \
        zip \
        gd \
        bcmath \
        intl \
        opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy dependency manifests first for better layer caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY package.json package-lock.json ./
RUN npm ci

# Copy application source
COPY . .

# Finish Composer autoloader and build frontend assets
RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && php artisan package:discover --ansi \
    && npm run build \
    && rm -rf node_modules \
    && sed -i 's/\r$//' docker/start.sh \
    && chmod +x docker/start.sh \
    && mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/php.ini /usr/local/etc/php/conf.d/99-laravel.ini
COPY docker/Caddyfile /etc/caddy/Caddyfile
RUN sed -i 's/\r$//' /etc/caddy/Caddyfile /usr/local/etc/php/conf.d/99-laravel.ini

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr

CMD ["/app/docker/start.sh"]
