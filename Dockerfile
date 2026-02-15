# ----------------------------
# Base Image
# ----------------------------
FROM php:8.4-apache

# ----------------------------
# System Dependencies & PHP Extensions
# ----------------------------
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    curl \
    zip \
    libicu-dev \
    g++ \
    nodejs \
    npm \
    && docker-php-ext-install pdo pdo_mysql zip intl mbstring xml bcmath opcache

# Enable Apache rewrite
RUN a2enmod rewrite

# ----------------------------
# Set working directory
# ----------------------------
WORKDIR /var/www/html

# ----------------------------
# Copy composer files & install PHP deps
# ----------------------------
COPY composer.json composer.lock ./

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer
RUN COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader

# ----------------------------
# Copy Node files & build Vite assets
# ----------------------------
COPY package*.json ./
RUN npm ci --only=production
RUN npm run build

# ----------------------------
# Copy full application code
# ----------------------------
COPY . .

# ----------------------------
# Set storage and cache permissions
# ----------------------------
RUN chown -R www-data:www-data storage bootstrap/cache

# ----------------------------
# Set Apache Document Root
# ----------------------------
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# ----------------------------
# Expose port 80
# ----------------------------
EXPOSE 80

# ----------------------------
# Start Apache
# ----------------------------
CMD ["apache2-foreground"]
