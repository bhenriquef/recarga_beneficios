FROM php:8.2-fpm

ENV DEBIAN_FRONTEND=noninteractive

# Instala dependências do sistema e extensões PHP necessárias
RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libpq-dev \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libzip-dev libxml2-dev \
    build-essential autoconf pkg-config \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) \
      pdo pdo_pgsql gd zip mbstring xml ctype intl bcmath \
 && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copia o Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copia o código-fonte
COPY . .

# Instala as dependências do Laravel
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Permissões para o Laravel
RUN chmod -R 777 storage bootstrap/cache || true

# Limpa caches (sem quebrar o build)
RUN php artisan config:clear || true \
 && php artisan cache:clear || true \
 && php artisan route:clear || true \
 && php artisan view:clear || true

EXPOSE 8000

# Usa a variável PORT do Render (necessário)
CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"]
