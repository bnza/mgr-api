#!/bin/bash
# Generate a temporary self-signed SSL certificate so nginx can start.
# Run this once on a fresh server before the first "docker compose up".
# Skips if certificates already exist.
#
# Reads NGINX_HOST from the .env file.
#
# Usage: ./docker/certbot/init-certs.sh

set -euo pipefail

if [ -f .env ]; then
    while IFS='=' read -r key value || [ -n "$key" ]; do
        key=$(echo "$key" | tr -d '\r')
        value=$(echo "$value" | tr -d '\r')
        case "$key" in '#'*) continue ;; esac
        [ -n "$key" ] && export "$key=$value"
    done < .env
fi

DOMAIN="${NGINX_HOST:?NGINX_HOST is not set in .env}"

CERT_DIR="./docker/certbot/conf/live/${DOMAIN}"

if [ -d "$CERT_DIR" ] && [ -f "$CERT_DIR/fullchain.pem" ]; then
    echo "Certificates already exist for ${DOMAIN}. Skipping."
    echo "To regenerate, remove ${CERT_DIR} and run this script again."
    exit 0
fi

echo "==> Creating temporary self-signed certificate for ${DOMAIN}..."
mkdir -p "$CERT_DIR"
mkdir -p "./docker/certbot/www"
openssl req -x509 -nodes -newkey rsa:2048 -days 1 \
    -keyout "$CERT_DIR/privkey.pem" \
    -out "$CERT_DIR/fullchain.pem" \
    -subj "/CN=${DOMAIN}" 2>/dev/null

echo "==> Done! Temporary certificate created at ${CERT_DIR}."
echo "    You can now start nginx with: docker compose up -d nginx"
echo "    Then run renew-certs.sh to obtain a real Let's Encrypt certificate."
