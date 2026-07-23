FROM php:8.3-cli-alpine

# Install system dependencies
RUN apk add --no-cache \
    nodejs \
    npm \
    git \
    curl \
    sqlite \
    sqlite-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    zip \
    unzip

# Install PHP SQLite and GD extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_sqlite gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy application source code
COPY . /app

# Install dependencies and build assets
RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build

# Create SQLite database file if it does not exist
RUN touch /app/database/database.sqlite
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache /app/database

EXPOSE 10000

# Start command: run migrations & start server
CMD ["sh", "-c", "php artisan migrate --force && php artisan db:seed --force && php artisan config:clear && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
