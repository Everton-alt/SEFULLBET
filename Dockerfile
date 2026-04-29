# Usa a imagem oficial do PHP com Apache
FROM php:8.2-apache

# Instala dependências do sistema e extensões para PostgreSQL e MySQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql

# Habilita o mod_rewrite do Apache (essencial para rotas amigáveis)
RUN a2enmod rewrite

# Copia todos os arquivos do seu repositório para o diretório do servidor
COPY . /var/www/html/

# Ajusta as permissões para que o Apache possa ler os arquivos
RUN chown -R www-data:www-data /var/www/html

# Ajuste crucial para o Render: 
# O Render passa a porta na variável $PORT, mas o Apache vem configurado para a 80.
# Este comando troca a porta 80 pela porta que o Render fornecer.
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Define quais arquivos o servidor deve procurar primeiro
RUN echo "DirectoryIndex login.php index.php" >> /etc/apache2/apache2.conf

# Informa a porta (apenas como referência)
EXPOSE 80

# Inicia o Apache em primeiro plano
CMD ["apache2-foreground"]
