# Usa imagem PHP 8.2 FPM oficial
FROM php:8.2-fpm

# Evita prompts interativos
ENV DEBIAN_FRONTEND=noninteractive

# Instala dependências do sistema e extensões PHP necessárias
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpq-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libzip-dev libonig-dev libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql gd zip mbstring tokenizer xml ctype \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copia o Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Define o diretório de trabalho
WORKDIR /var/www/html

# Copia o código-fonte do Laravel
COPY . .

# Instala dependências do Laravel
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Garante permissões corretas para storage e cache
RUN chmod -R 777 storage bootstrap/cache

# Limpa caches (sem quebrar se o .env não existir ainda)
RUN php artisan config:clear || true \
    && php artisan cache:clear || true \
    && php artisan route:clear || true \
    && php artisan view:clear || true

# Expõe a porta padrão
EXPOSE 8000

# Inicia o servidor PHP embutido do Laravel
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
