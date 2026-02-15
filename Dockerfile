FROM php:8.4-apache

# System deps
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    curl \
    nodejs \
    npm \
    && docker-php-ext-install pdo pdo_mysql zip

# Enable Apache rewrite
RUN a2enmod rewrite

WORKDIR /var/www/html

# Copy composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy PHP files
COPY . .

# Install PHP deps
RUN composer install --no-dev --optimize-autoloader

# Install Node deps + build Vite assets
RUN npm ci --only=production
RUN npm run build

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache

# Set Apache document root
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

EXPOSE 80
