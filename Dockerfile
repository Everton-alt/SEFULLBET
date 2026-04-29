FROM php:8.2-apache

# Habilita o mod_rewrite do Apache
RUN a2enmod rewrite

# Copia todos os arquivos da raiz do GitHub para dentro do servidor
COPY . /var/www/html/

# Ajusta as permissões para que o servidor consiga ler os arquivos
RUN chown -R www-data:www-data /var/www/html

# Define que o arquivo padrão de entrada é o login.php (ou index.php)
RUN echo "DirectoryIndex login.php index.php" >> /etc/apache2/apache2.conf

# Porta padrão que o Render espera
ENV PORT=80
EXPOSE 80

CMD ["apache2-foreground"]
