FROM php:8.3-cli-alpine

# Install system dependencies
RUN apk add --no-cache \
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

# Set PHP upload limits (25MB) to allow receipt image uploads
RUN echo "upload_max_filesize = 25M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 25M" >> /usr/local/etc/php/conf.d/uploads.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy application source code
COPY . /app

# Install PHP production dependencies cleanly without build-time scripts
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Ensure permissions for runtime storage and cache
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache /app/database

EXPOSE 10000

# Start command: create .env if missing, inject GEMINI_API_KEY, generate APP_KEY, run migrations & start server
CMD ["sh", "-c", "if [ ! -f .env ]; then cp .env.example .env; fi && if [ -n \"$GEMINI_API_KEY\" ]; then sed -i '/GEMINI_API_KEY=/d' .env && echo \"GEMINI_API_KEY=$GEMINI_API_KEY\" >> .env; fi && mkdir -p /app/storage/database /app/database && if [ ! -f /app/storage/database/database.sqlite ] && [ ! -f /app/database/database.sqlite ]; then touch /app/database/database.sqlite; fi && chmod -R 777 /app/database /app/storage /app/bootstrap/cache && php artisan key:generate --force && php artisan migrate --force && php artisan config:clear && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
