#!/bin/sh
set -e

# Copy Aiven SSL certificate from Render secrets if present
if [ -f /etc/secrets/aiven-ca.pem ]; then
    cp /etc/secrets/aiven-ca.pem /tmp/aiven-ca.pem
    chmod 644 /tmp/aiven-ca.pem
fi

# Execute the container's command (Apache for web, or PHP for cron)
exec "$@"
