#!/bin/sh
# Arranque del contenedor: puerto de Railway, carpetas escribibles y Apache en primer plano.
set -e

PORT="${PORT:-8080}"
export PORT
sed -ri "s/^Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf

# El volumen de Railway llega vacío y con dueño root: Apache (www-data) tiene que poder escribir las fotos.
for d in /var/www/html/storage/sesiones /var/www/html/storage/importaciones /var/www/html/public/assets/img/subidas; do
  mkdir -p "$d"
  chown -R www-data:www-data "$d"
done

exec apache2-foreground
