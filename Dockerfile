
FROM dunglas/frankenphp:1-php8.3-alpine

RUN install-php-extensions \
    pdo_mysql \
    mbstring \
    bcmath \
    pcntl \
    intl \
    zip \
    gd \
    opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader

RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr

EXPOSE 10000

CMD ["sh", "-c", "frankenphp php-server --listen 0.0.0.0:${PORT:-10000} --root public/"]
