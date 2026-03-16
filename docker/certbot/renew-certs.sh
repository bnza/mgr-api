#!/bin/bash
# Request or renew Let's Encrypt SSL certificates via certbot.
# On first run, obtains a new certificate. On subsequent runs, renews if needed.
# Suitable for crontab automation.
#
# Reads NGINX_HOST from the .env file.
# Optionally reads CERTBOT_EMAIL from the .env file.
#
# Usage: ./docker/certbot/renew-certs.sh

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
EMAIL="${CERTBOT_EMAIL:-}"

EMAIL_ARG=""
if [ -n "$EMAIL" ]; then
    EMAIL_ARG="--email ${EMAIL}"
else
    EMAIL_ARG="--register-unsafely-without-email"
fi

echo "==> Requesting/renewing Let's Encrypt certificate for ${DOMAIN}..."
docker compose run --rm certbot certonly \
    --webroot \
    --webroot-path=/var/www/certbot/ \
    --agree-tos \
    --keep-until-expiring \
    ${EMAIL_ARG} \
    -d "${DOMAIN}"

echo "==> Reloading nginx..."
docker compose exec nginx nginx -s reload

echo "==> Done! Certificate for ${DOMAIN} is up to date."
