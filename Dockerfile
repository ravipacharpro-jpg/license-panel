FROM php:8.2-apache

# KURO panel (CodeIgniter 4) needs: mysqli + pdo_mysql (optional MySQL mode),
# sqlite3 + pdo_sqlite (default zero-config SQLite mode), intl, mbstring.
RUN set -eux; \
    for i in 1 2 3 4 5; do apt-get update && break || { echo "apt-get update retry $i"; sleep 12; }; done; \
    apt-get install -y --no-install-recommends --fix-missing libicu-dev libsqlite3-dev; \
    docker-php-ext-install mysqli pdo pdo_mysql sqlite3 pdo_sqlite intl mbstring; \
    a2enmod rewrite headers; \
    rm -rf /var/lib/apt/lists/*; \
    (php -m | grep -Ei '^(sqlite3|pdo_sqlite|intl|mbstring)$' || (echo 'REQUIRED PHP EXT MISSING'; php -m; exit 1))

# KURO panel serves from repo ROOT (front controller: index.php).
# .htaccess routes everything + blocks app/system/writable.
ENV APACHE_DOCUMENT_ROOT=/var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf || true

WORKDIR /var/www/html
COPY . /var/www/html/

RUN mkdir -p writable/cache writable/logs writable/session writable/uploads \
 && chown -R www-data:www-data writable \
 && chmod -R 777 writable

EXPOSE 80
CMD ["apache2-foreground"]
