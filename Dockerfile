# ==========================================
# Stage 1: Build Dependencies
# ==========================================
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --ignore-platform-reqs \
    --optimize-autoloader \
    --no-scripts

# ==========================================
# Stage 2: Production PHP-FPM Runtime
# ==========================================
FROM php:8.4-fpm-alpine

LABEL maintainer="Stronger Muscles Team"

WORKDIR /var/www/html

# Install system dependencies & PHP extensions
RUN apk add --no-cache \
    curl \
    libpng \
    libpng-dev \
    libjpeg-turbo \
    libjpeg-turbo-dev \
    freetype \
    freetype-dev \
    libzip \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    postgresql-dev \
    $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        opcache \
        gd \
        zip \
        intl \
        bcmath \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del --no-cache $PHPIZE_DEPS freetype-dev libpng-dev libjpeg-turbo-dev libzip-dev

# Copy PHP and OPcache configuration
COPY docker/php/php.ini $PHP_INI_DIR/conf.d/custom.ini

# Copy entrypoint script
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Copy application source code
COPY . /var/www/html

# Copy pre-built vendor from stage 1
COPY --from=vendor /app/vendor /var/www/html/vendor

# Create runtime directories and set proper ownership
RUN mkdir -p /var/www/html/storage/app/public \
             /var/www/html/storage/framework/cache/data \
             /var/www/html/storage/framework/sessions \
             /var/www/html/storage/framework/views \
             /var/www/html/storage/logs \
             /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]
