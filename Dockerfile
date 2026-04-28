FROM php:8.2-apache

# Habilita o mod_rewrite do Apache (comum em projetos PHP)
RUN a2enmod rewrite

# Copia os arquivos do seu projeto para a pasta do servidor
COPY . /var/www/html/

# Ajusta as permissões para o servidor ler os arquivos
RUN chown -R www-data:www-data /var/www/html

# Define a porta que o Render exige
ENV PORT=80
EXPOSE 80

# Comando para iniciar o Apache
CMD ["apache2-foreground"]
