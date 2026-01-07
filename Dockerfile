FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create www-data user home directory and set proper permissions
RUN mkdir -p /var/www/.cache /var/www/.composer /var/www/.npm \
    && chown -R www-data:www-data /var/www

# Set environment variables for Composer and npm
ENV COMPOSER_HOME=/var/www/.composer
ENV COMPOSER_CACHE_DIR=/var/www/.cache/composer
ENV npm_config_cache=/var/www/.npm

# Expose port 8000 and start php-fpm server
EXPOSE 8000
