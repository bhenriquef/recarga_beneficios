# Imagem base com PHP 8.2 e FPM
FROM php:8.2-fpm

# Instala dependências do sistema e extensões PHP necessárias para Laravel
RUN apt-get update && apt-get install -y \
    git curl zip unzip libzip-dev libpq-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql gd zip

# Instala Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Define o diretório de trabalho
WORKDIR /var/www/html

# Copia arquivos do projeto
COPY . .

# Instala dependências do Laravel (ignorando pacotes de dev)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Gera cache de configurações
RUN php artisan config:cache || true \
    && php artisan route:cache || true \
    && php artisan view:cache || true

# Expõe a porta 8000
EXPOSE 8000

# Comando padrão para iniciar o servidor Laravel
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
