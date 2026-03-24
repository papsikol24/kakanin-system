FROM php:8.2-apache

# First, remove all MPM modules before installing anything else
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load || true

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libssl-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

RUN pecl install mongodb && docker-php-ext-enable mongodb

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Ensure only prefork is enabled
RUN a2enmod mpm_prefork rewrite

WORKDIR /var/www/html

COPY . /var/www/html/

RUN composer install --no-interaction --optimize-autoloader --no-dev || true

RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

EXPOSE 80