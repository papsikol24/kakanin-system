FROM php:8.2-apache 
 
    git curl libpng-dev libonig-dev libxml2-dev zip unzip libssl-dev \ 
 
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd 
 
RUN pecl install mongodb && docker-php-ext-enable mongodb 
 
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer 
 
RUN a2enmod rewrite 
 
WORKDIR /var/www/html 
 
COPY . /var/www/html/ 
 
RUN composer install --no-interaction --optimize-autoloader --no-dev || true 
 
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html 
 
EXPOSE 80 
