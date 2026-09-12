FROM php:8.1-apache

# CI4 extensions (retry-proof apt for flaky builders)
RUN set -eux; \
    for i in 1 2 3 4 5; do apt-get update && break || { echo "apt-get update retry $i"; sleep 12; }; done; \
    apt-get install -y --no-install-recommends --fix-missing libicu-dev; \
    docker-php-ext-install mysqli pdo pdo_mysql intl mbstring; \
    a2enmod rewrite headers; \
    rm -rf /var/lib/apt/lists/*; \
    php -m | grep -Ei '^(mysqli|intl|mbstring|pdo_mysql)$'

# KURO panel serves from repo ROOT (front controller: index.php).
# .htaccess routes everything + blocks app/system/writable.
ENV APACHE_DOCUMENT_ROOT=/var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf || true

WORKDIR /var/www/html
COPY . /var/www/html/

RUN mkdir -p /var/www/html/writable/cache /var/www/html/writable/logs /var/www/html/writable/session /var/www/html/writable/uploads \
 && chown -R www-data:www-data /var/www/html/writable \
 && chmod -R 775 /var/www/html/writable

EXPOSE 80
CMD ["apache2-foreground"]
