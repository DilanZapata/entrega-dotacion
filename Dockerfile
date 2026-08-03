FROM php:8.2-apache

# Permite que los archivos .htaccess (usados para proteger data/) funcionen.
RUN sed -ri -e 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && a2enmod rewrite

COPY . /var/www/html/

# El servidor web (www-data) necesita poder escribir los JSON en data/.
RUN chown -R www-data:www-data /var/www/html/data \
    && chmod -R 775 /var/www/html/data

EXPOSE 80
