FROM php:8.2-apache
RUN docker-php-ext-install pdo pdo_mysql

RUN a2enmod ssl && a2ensite default-ssl

RUN openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/ssl/private/ssl-cert-snakeoil.key \
    -out /etc/ssl/certs/ssl-cert-snakeoil.pem \
    -subj "/CN=localhost"

EXPOSE 80
EXPOSE 443