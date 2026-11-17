#!/usr/bin/env bash
# =====================================================================
# Instala el sistema de trazabilidad en un servidor Ubuntu 24.04 limpio
# (droplet de DigitalOcean). Uso, como root:
#   curl -fsSL https://raw.githubusercontent.com/USUARIO/REPO/main/deploy/servidor.sh -o s.sh && bash s.sh https://github.com/USUARIO/REPO.git
# =====================================================================
set -euo pipefail

REPO="${1:?Falta la URL del repositorio de GitHub}"
APP=/var/www/trazabilidad
export DEBIAN_FRONTEND=noninteractive

IP="$(curl -fsS4 --max-time 10 https://api.ipify.org || hostname -I | awk '{print $1}')"
HOST="$(echo "$IP" | tr . -).sslip.io"

paso() { echo; echo "==> $*"; }

paso "[1/7] Instalando Nginx, MySQL y PHP (unos 3 a 5 minutos)"
apt-get update -y -qq
apt-get install -y -qq nginx mysql-server git unzip curl openssl ufw \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl \
  php8.3-zip php8.3-bcmath php8.3-intl composer certbot python3-certbot-nginx >/dev/null

paso "[2/7] Descargando el sistema desde GitHub"
rm -rf "$APP"
git clone --depth 1 "$REPO" "$APP"
cd "$APP"
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction --quiet

paso "[3/7] Creando la base de datos mv_trazabilidad"
DBPASS="$(openssl rand -hex 16)"
mysql -e "CREATE DATABASE IF NOT EXISTS mv_trazabilidad CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'trazabilidad'@'localhost' IDENTIFIED BY '$DBPASS';
ALTER USER 'trazabilidad'@'localhost' IDENTIFIED BY '$DBPASS';
GRANT ALL PRIVILEGES ON mv_trazabilidad.* TO 'trazabilidad'@'localhost';
FLUSH PRIVILEGES;"

paso "[4/7] Configurando Laravel en modo producción"
DEMO_PASSWORD="$(openssl rand -base64 18 | tr -dc 'A-Za-z0-9' | head -c 10)"
cp .env.example .env
set_env() {
  if grep -qE "^#? ?$1=" .env; then sed -i -E "s|^#? ?$1=.*|$1=$2|" .env; else echo "$1=$2" >> .env; fi
}
set_env APP_NAME "\"Trazabilidad MV\""
set_env APP_ENV production
set_env APP_DEBUG false
set_env APP_URL "http://$HOST"
set_env APP_LOCALE es
set_env LOG_LEVEL warning
set_env DB_CONNECTION mysql
set_env DB_HOST 127.0.0.1
set_env DB_PORT 3306
set_env DB_DATABASE mv_trazabilidad
set_env DB_USERNAME trazabilidad
set_env DB_PASSWORD "$DBPASS"
set_env DEMO_PASSWORD "$DEMO_PASSWORD"
php artisan key:generate --force --quiet
php artisan migrate:fresh --seed --force
php artisan config:cache --quiet
php artisan route:cache --quiet
php artisan view:cache --quiet
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
chmod 640 .env && chown root:www-data .env

paso "[5/7] Configurando el servidor web"
cat > /etc/nginx/sites-available/trazabilidad <<NGINX
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name $HOST $IP;
    root $APP/public;
    index index.php;
    charset utf-8;
    server_tokens off;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    location / { try_files \$uri \$uri/ /index.php?\$query_string; }
    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }
    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
    }
    location ~ /\.(?!well-known).* { deny all; }
}
NGINX
rm -f /etc/nginx/sites-enabled/default
ln -sf /etc/nginx/sites-available/trazabilidad /etc/nginx/sites-enabled/trazabilidad
nginx -t -q
systemctl reload nginx

paso "[6/7] Activando el firewall (solo SSH y web)"
ufw allow OpenSSH >/dev/null
ufw allow 'Nginx Full' >/dev/null
ufw --force enable >/dev/null

paso "[7/7] Intentando activar HTTPS (candado)"
URL="http://$HOST"
if certbot --nginx -d "$HOST" --non-interactive --agree-tos --register-unsafely-without-email --redirect >/dev/null 2>&1; then
  URL="https://$HOST"
  set_env APP_URL "$URL"
  php artisan config:cache --quiet
else
  echo "No se pudo activar HTTPS; el sistema funciona igual por http."
fi

cat <<FIN

=====================================================================
  LISTO. El sistema está en internet:

    $URL

  Usuarios: lorenzo@mvtextil.pe (Administrador), alejandro@,
            almacen@, ventas@ y consulta@mvtextil.pe
  Contraseña de todos:  $DEMO_PASSWORD

  Copia la contraseña ahora: no se vuelve a mostrar.
=====================================================================
FIN
