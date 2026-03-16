#!/bin/sh
# Request or renew Let's Encrypt SSL certificates via certbot.
# Runs INSIDE the certbot container.
# On first run, obtains a new certificate. On subsequent runs, renews if needed.
# Suitable for crontab automation.
#
# Expects environment variables: NGINX_HOST, USER_UID, USER_GID
# Optionally: CERTBOT_EMAIL
#
# Usage: docker compose run --rm certbot /opt/certbot-scripts/renew-certs.sh

set -eu

DOMAIN="${NGINX_HOST:?NGINX_HOST is not set}"
EMAIL="${CERTBOT_EMAIL:-}"

EMAIL_ARG=""
if [ -n "$EMAIL" ]; then
    EMAIL_ARG="--email ${EMAIL}"
else
    EMAIL_ARG="--register-unsafely-without-email"
fi

RENEWAL_CONF="/etc/letsencrypt/renewal/${DOMAIN}.conf"

# If no certbot renewal config exists, the certs are self-signed (from init-certs.sh).
# Remove them so certbot can create its own directory structure.
if [ ! -f "$RENEWAL_CONF" ] && [ -d "/etc/letsencrypt/live/${DOMAIN}" ]; then
    echo "==> Removing temporary self-signed certificate..."
    rm -rf "/etc/letsencrypt/live/${DOMAIN}"
fi

echo "==> Requesting/renewing Let's Encrypt certificate for ${DOMAIN}..."
certbot certonly \
    --webroot \
    --webroot-path=/var/www/certbot/ \
    --agree-tos \
    --keep-until-expiring \
    ${EMAIL_ARG} \
    -d "${DOMAIN}"

chown -R "${USER_UID:-1000}:${USER_GID:-1000}" /etc/letsencrypt/

echo "==> Done! Certificate for ${DOMAIN} is up to date."
echo "    Remember to reload nginx: docker compose exec nginx nginx -s reload"
