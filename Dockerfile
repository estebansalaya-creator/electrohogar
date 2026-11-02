# Electrohogar - imagen para Railway (o cualquier Docker): PHP 8.3 + Apache.
# En Windows/IIS no se usa: ver herramientas\configurar_iis.bat.
FROM php:8.3-apache

# Extensiones: gd con JPEG/PNG/WebP (fotos del panel), pdo_mysql y opcache.
# curl, openssl, mbstring y sodium ya vienen en la imagen oficial.
RUN apt-get update \
 && apt-get install -y --no-install-recommends libjpeg62-turbo-dev libpng-dev libwebp-dev libfreetype6-dev \
 && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
 && docker-php-ext-install -j"$(nproc)" gd pdo_mysql opcache \
 && rm -rf /var/lib/apt/lists/*

COPY docker/php.ini /usr/local/etc/php/conf.d/electrohogar.ini
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/arranque.sh /usr/local/bin/arranque.sh

WORKDIR /var/www/html
COPY . /var/www/html

RUN chmod +x /usr/local/bin/arranque.sh \
 && echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
 && a2enconf servername \
 && a2enmod headers \
 && mkdir -p storage/sesiones storage/importaciones public/assets/img/subidas \
 && chown -R www-data:www-data storage public/assets/img/subidas

# Railway monta el volumen en public/assets/img/subidas y define PORT.
ENV PORT=8080
EXPOSE 8080
CMD ["/usr/local/bin/arranque.sh"]
