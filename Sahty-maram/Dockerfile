FROM php:8.2-apache

# Enable mod_rewrite for Symfony routing
RUN a2enmod rewrite

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        pdo_mysql \
        zip \
        intl \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy application code
COPY . /app

# Set Apache DocumentRoot
RUN sed -i 's|/var/www/html|/app/public|g' /etc/apache2/sites-available/*.conf

# Set proper permissions
RUN chown -R www-data:www-data /app

# Install PHP dependencies
RUN composer install --no-interaction --prefer-dist

# Expose port
EXPOSE 8000

# Start Apache
CMD ["apache2-foreground"]
