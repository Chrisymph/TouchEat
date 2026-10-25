
FROM php:8.3-apache-bookworm

# Installer les dépendances système nécessaires aux extensions PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        bcmath \
        pcntl \
        intl \
        zip \
        gd \
        opcache \
    && update-ca-certificates \
    && rm -rf /var/lib/apt/lists/*


# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

# Certificat CA pour la connexion TLS à TiDB Cloud
COPY certs/isrgrootx1.pem /etc/ssl/certs/isrgrootx1.pem

# Copier le projet Laravel
COPY . .

# Configurer Apache pour servir le dossier public de Laravel
RUN sed -ri 's!/var/www/html!/app/public!g' \
        /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /app/public>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n' > /etc/apache2/conf-available/laravel.conf \
    && a2enmod rewrite \
    && a2enconf laravel

# Installer les dépendances PHP de production
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader

# Préparer les répertoires Laravel et leurs permissions
RUN mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr

EXPOSE 10000

# Faire écouter Apache sur le port fourni par Render
CMD ["sh", "-c", "sed -i \"s/Listen 80/Listen ${PORT:-10000}/\" /etc/apache2/ports.conf && sed -i \"s/<VirtualHost \\*:80>/<VirtualHost *:${PORT:-10000}>/\" /etc/apache2/sites-available/000-default.conf && exec apache2-foreground"]
