FROM php:8.2-apache

# Permite que los archivos .htaccess (usados para proteger data/) funcionen.
RUN sed -ri -e 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && a2enmod rewrite

COPY . /var/www/html/

# El servidor web (www-data) necesita poder escribir los JSON en data/
# y los documentos escaneados de EPP en uploads/.
RUN chown -R www-data:www-data /var/www/html/data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/data /var/www/html/uploads

# Permite subir escaneados/fotos de tamaño razonable (por defecto PHP limita a 2M).
RUN echo "upload_max_filesize = 20M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 25M" >> /usr/local/etc/php/conf.d/uploads.ini

EXPOSE 80
