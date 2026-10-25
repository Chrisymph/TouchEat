FROM php:8.3-apache-bookworm

RUN set -eux; \
    id; \
    command -v frankenphp; \
    ls -l "$(command -v frankenphp)"; \
    frankenphp version; \
    echo "DIAGNOSTIC_FRANKENPHP_OK"


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

COPY certs/isrgrootx1.pem /etc/ssl/certs/isrgrootx1.pem

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

CMD ["frankenphp", "run", "--config", "/app/Caddyfile"]
