# --- Base PHP image with required extensions ---
    FROM php:8.3-fpm

    # Install system dependencies
    RUN apt-get update && apt-get install -y \
        curl \
        git \
        unzip \
        zip \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        libzip-dev \
        && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

    # Install Composer
    COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

    # Set working directory
    WORKDIR /var/www/html

    # Copy everything first (so artisan is available during composer install)
    COPY . .

    # Install PHP dependencies (with artisan available)
    # RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
    RUN composer install

    # Install Node & NPM deps (for Vite + Tailwind)
    RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
        && apt-get install -y nodejs \
        && npm install \
        && npm run build

    EXPOSE 8000

    CMD ["php-fpm"]
    # COPY entrypoint.sh /usr/local/bin/entrypoint.sh
    # RUN chmod +x /usr/local/bin/entrypoint.sh
    # ENTRYPOINT ["entrypoint.sh"]