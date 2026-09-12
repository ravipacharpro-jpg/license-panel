FROM php:8.2-apache

# PHP extensions for SQLite + MySQL
RUN apt-get update && apt-get install -y --no-install-recommends \
    libsqlite3-dev sqlite3 \
 && docker-php-ext-install pdo pdo_sqlite pdo_mysql mysqli \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*

# Apache DocumentRoot -> /var/www/html/public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf || true

WORKDIR /var/www/html
COPY . /var/www/html/

# writable sqlite dir + apache perms
RUN mkdir -p /var/www/html/data \
 && chown -R www-data:www-data /var/www/html/data /var/www/html/public \
 && chmod -R 775 /var/www/html/data

EXPOSE 80
CMD ["apache2-foreground"]
