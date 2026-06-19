# Multi-stage build for CodeIgniter 4 application
FROM php:8.2-fpm as builder

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libicu-dev \
    && docker-php-ext-install -j$(nproc) \
    intl \
    mbstring \
    json \
    pdo_mysql \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files and install dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Final stage
FROM php:8.2-fpm

# Install runtime dependencies
RUN apt-get update && apt-get install -y \
    libicu-dev \
    && docker-php-ext-install -j$(nproc) \
    intl \
    mbstring \
    json \
    pdo_mysql \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Configure PHP
COPY php.ini /usr/local/etc/php/conf.d/app.ini

WORKDIR /app

# Copy vendor from builder
COPY --from=builder /app/vendor /app/vendor

# Copy application code
COPY . .

# Create writable directories for CodeIgniter
RUN mkdir -p writable && chmod -R 777 writable

# Create non-root user
RUN useradd -m -u 1000 appuser && chown -R appuser:appuser /app
USER appuser

EXPOSE 9000

CMD ["php-fpm"]
