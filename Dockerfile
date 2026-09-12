FROM php:8.2-apache

# MySQLi for MEXX panel
RUN apt-get update && apt-get install -y --no-install-recommends \
 && docker-php-ext-install mysqli pdo pdo_mysql \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . /var/www/html/

# writable sessions dir
RUN mkdir -p /var/www/html/sessions \
 && chown -R www-data:www-data /var/www/html/sessions \
 && chmod -R 775 /var/www/html/sessions

EXPOSE 80
CMD ["apache2-foreground"]
