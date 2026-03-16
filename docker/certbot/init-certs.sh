#!/bin/sh
# Generate a temporary self-signed SSL certificate so nginx can start.
# Runs INSIDE the certbot container.
# Skips if certificates already exist.
#
# Expects environment variables: NGINX_HOST, USER_UID, USER_GID
#
# Usage: docker compose run --rm certbot /opt/certbot-scripts/init-certs.sh

set -eu

DOMAIN="${NGINX_HOST:?NGINX_HOST is not set}"
CERT_DIR="/etc/letsencrypt/live/${DOMAIN}"

if [ -d "$CERT_DIR" ] && [ -f "$CERT_DIR/fullchain.pem" ]; then
    echo "Certificates already exist for ${DOMAIN}. Skipping."
    echo "To regenerate, remove docker/certbot/conf/live/${DOMAIN} and run this script again."
    exit 0
fi

echo "==> Creating temporary self-signed certificate for ${DOMAIN}..."
mkdir -p "$CERT_DIR"
openssl req -x509 -nodes -newkey rsa:2048 -days 1 \
    -keyout "$CERT_DIR/privkey.pem" \
    -out "$CERT_DIR/fullchain.pem" \
    -subj "/CN=${DOMAIN}" 2>/dev/null

chown -R "${USER_UID:-1000}:${USER_GID:-1000}" /etc/letsencrypt/

echo "==> Done! Temporary certificate created for ${DOMAIN}."
echo "    You can now start nginx with: docker compose up -d nginx"
echo "    Then run: docker compose run --rm certbot /opt/certbot-scripts/renew-certs.sh"
