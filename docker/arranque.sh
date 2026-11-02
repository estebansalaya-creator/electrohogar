#!/bin/sh
# Arranque del contenedor: puerto de Railway, carpetas escribibles y Apache en primer plano.
set -e

PORT="${PORT:-8080}"
export PORT
sed -ri "s/^Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf

# PHP dentro de Apache necesita un solo MPM: prefork. La imagen oficial apaga mpm_event al construirse,
# pero en Railway ese módulo reaparece al arrancar y Apache se cae con "More than one MPM loaded".
# Por eso se deja solo prefork acá, en cada arranque.
rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.*
for f in mpm_prefork.load mpm_prefork.conf; do
  if [ -e "/etc/apache2/mods-available/$f" ]; then
    ln -sf "../mods-available/$f" "/etc/apache2/mods-enabled/$f"
  fi
done
echo "MPM activo: $(ls /etc/apache2/mods-enabled | grep '^mpm_.*\.load$' | tr '\n' ' ')"

# El volumen de Railway llega vacío y con dueño root: Apache (www-data) tiene que poder escribir las fotos.
for d in /var/www/html/storage/sesiones /var/www/html/storage/importaciones /var/www/html/public/assets/img/subidas; do
  mkdir -p "$d"
  chown -R www-data:www-data "$d"
done

exec apache2-foreground
