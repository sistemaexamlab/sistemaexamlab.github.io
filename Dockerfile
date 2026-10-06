FROM php:8.1-apache

# Instala extensões para MySQL, PDO e manipulação de arquivos
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Habilita o mod_rewrite do Apache
RUN a2enmod rewrite

# Copia todos os arquivos do repositório para a pasta web
COPY . /var/www/html/

EXPOSE 80
