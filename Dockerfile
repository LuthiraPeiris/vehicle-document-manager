FROM php:8.3-apache

# Install system packages required by PHP extensions and Composer
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libzip-dev \
        unzip \
    && docker-php-ext-install \
        pdo_mysql \
        zip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . /var/www/html/

RUN a2enmod rewrite

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN composer install --no-dev --prefer-dist --optimize-autoloader

EXPOSE 10000

CMD ["sh", "-c", "sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf && sed -i 's/:80>/:10000>/' /etc/apache2/sites-available/000-default.conf && apache2-foreground"]