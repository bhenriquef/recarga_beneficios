# Usa imagem oficial do PHP com extensões necessárias
FROM php:8.2-fpm

# Instala dependências do sistema
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpq-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-install pdo pdo_pgsql gd

# Instala o Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Define diretório de trabalho
WORKDIR /var/www/html

# Copia os arquivos do projeto
COPY . .

# Instala dependências do Laravel
RUN composer install --no-dev --optimize-autoloader

# Gera cache das configurações
RUN php artisan config:cache && php artisan route:cache && php artisan view:cache

# Expõe a porta 8000 (Laravel)
EXPOSE 8000

# Comando para iniciar o servidor
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
