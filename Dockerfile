# Dockerfile for CodeIgniter 4 — Railway-ready (single-container Apache)
FROM php:8.2-apache

# Install system dependencies + PHP extensions needed by CI4, dompdf, phpspreadsheet, qr-code
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    zlib1g-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    intl \
    pdo_mysql \
    zip \
    gd \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite for CodeIgniter clean URLs
RUN a2enmod rewrite

# Configure Apache: listen on $PORT (Railway injects PORT env var)
RUN sed -i 's/Listen 80/Listen ${PORT}/g' /etc/apache2/ports.conf \
    && sed -i 's/:80>/:${PORT}>/g' /etc/apache2/sites-available/000-default.conf

# Set DocumentRoot to CodeIgniter's public/ directory
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -i "s|/var/www/html|${APACHE_DOCUMENT_ROOT}|g" /etc/apache2/sites-available/000-default.conf \
    && sed -i "s|/var/www/html|${APACHE_DOCUMENT_ROOT}|g" /etc/apache2/apache2.conf

# Allow .htaccess overrides (required for CI4 routing)
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Configure PHP for production
COPY php.ini /usr/local/etc/php/conf.d/app.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files and install dependencies (layer caching)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy application code
COPY . .

# Create writable directories and set permissions
RUN mkdir -p writable/cache writable/logs writable/session writable/uploads writable/debugbar \
    && chmod -R 777 writable

# Copy startup script
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Default PORT for Railway
ENV PORT=8080

EXPOSE ${PORT}

CMD ["/usr/local/bin/start.sh"]
