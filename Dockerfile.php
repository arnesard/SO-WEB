# =========================
# Stage 1 - Build Vite
# =========================
FROM node:20-alpine AS vite-builder
WORKDIR /app

COPY package*.json ./
RUN npm install

COPY . .
RUN npm run build


# =========================
# Stage 2 - PHP-FPM
# =========================
FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring gd zip bcmath \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy Laravel source
COPY . .

# Copy hasil build Vite
COPY --from=vite-builder /app/public/build /var/www/public/build

# Install PHP deps
RUN composer install --no-dev --optimize-autoloader \
 && mkdir -p storage bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache public/build \
 && chmod -R 775 storage bootstrap/cache public/build

USER www-data

EXPOSE 9000
CMD ["php-fpm"]