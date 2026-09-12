# webdevops/php-apache ships mysqli + pdo_mysql + intl + mbstring prebuilt,
# so no apt/compile step (Render builders often fail on apt).
FROM webdevops/php-apache:8.1

# KURO panel serves from repo ROOT (front controller: index.php).
# .htaccess routes everything + blocks app/system/writable.
ENV WEB_DOCUMENT_ROOT=/app
ENV PHP_DATE_TIMEZONE=UTC

WORKDIR /app
COPY --chown=application:application . /app/

RUN mkdir -p writable/cache writable/logs writable/session writable/uploads \
 && chmod -R 777 writable \
 && (php -m | grep -Ei '^(mysqli|intl|mbstring|pdo_mysql)$' || (echo 'REQUIRED PHP EXT MISSING'; php -m; exit 1))

EXPOSE 80
