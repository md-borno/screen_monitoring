FROM php:8.4-apache

# Install system dependencies
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
    && docker-php-ext-install pdo pdo_mysql zip intl mbstring xml bcmath opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite headers

# Optimize PHP for production & low memory
RUN { \
    echo "memory_limit=256M"; \
    echo "max_execution_time=60"; \
    echo "upload_max_filesize=10M"; \
    echo "post_max_size=10M"; \
    echo "opcache.enable=1"; \
    echo "opcache.memory_consumption=128"; \
    echo "opcache.interned_strings_buffer=8"; \
    echo "opcache.max_accelerated_files=4000"; \
    } > /usr/local/etc/php/conf.d/production.ini

WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock* ./

# Install Composer & PHP dependencies
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer && \
    COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader --no-interaction && \
    composer clear-cache

# Copy application code (but NOT .env)
COPY . .

# Remove .env.example to avoid confusion
RUN rm -f .env.example

# Install Node dependencies & build assets
RUN npm ci --omit=dev && \
    npm run build && \
    npm cache clean --force

# Run Laravel cache commands (requires .env to exist temporarily)
RUN cp .env.example .env.temp 2>/dev/null || echo "APP_KEY=base64:temporary" > .env.temp && \
    php artisan config:cache --env=production || true && \
    rm -f .env.temp .env

# Set permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 storage bootstrap/cache public/build && \
    chmod -R 775 storage/logs storage/framework

# Configure Apache DocumentRoot
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf && \
    sed -i 's!<Directory /var/www/html!<Directory /var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Health check
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

EXPOSE 80

CMD ["apache2-foreground"]
