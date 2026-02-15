# Use official PHP 8.4 + Apache
FROM php:8.4-apache

# -----------------------
# 1️⃣ System dependencies
# -----------------------
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    curl \
    zip \
    && docker-php-ext-install pdo pdo_mysql zip

# -----------------------
# 2️⃣ Node 20 for Vite
# -----------------------
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Enable Apache rewrite
RUN a2enmod rewrite

# -----------------------
# 3️⃣ Set working directory
# -----------------------
WORKDIR /var/www/html

# -----------------------
# 4️⃣ Copy Composer & install PHP deps
# -----------------------
COPY composer.json composer.lock ./
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer
RUN composer install --no-dev --optimize-autoloader

# -----------------------
# 5️⃣ Copy package.json & install Node deps
# -----------------------
COPY package*.json ./
RUN npm ci --only=production

# -----------------------
# 6️⃣ Copy the rest of the app
# -----------------------
COPY . .

# -----------------------
# 7️⃣ Build Vite assets
# -----------------------
RUN npm run build

# -----------------------
# 8️⃣ Set permissions
# -----------------------
RUN chown -R www-data:www-data storage bootstrap/cache

# -----------------------
# 9️⃣ Set Apache root to public
# -----------------------
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# -----------------------
# 10️⃣ Expose HTTP
# -----------------------
EXPOSE 80

# -----------------------
# 11️⃣ Start Apache
# -----------------------
CMD ["apache2-foreground"]
