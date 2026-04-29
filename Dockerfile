FROM php:8.2-apache

# Instala dependências do sistema e extensões do PHP para Banco de Dados
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql

# Habilita o mod_rewrite do Apache
RUN a2enmod rewrite

# Copia todos os arquivos da raiz para o servidor
COPY . /var/www/html/

# Ajusta as permissões
RUN chown -R www-data:www-data /var/www/html

# Define o arquivo inicial
RUN echo "DirectoryIndex login.php index.php" >> /etc/apache2/apache2.conf

ENV PORT=80
EXPOSE 80

CMD ["apache2-foreground"]
