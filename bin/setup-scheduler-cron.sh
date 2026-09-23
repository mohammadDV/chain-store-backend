#!/bin/sh
# Writes cron + env used by bin/run-schedule.sh. Called from docker-compose on boot.
set -e

ENV_FILE=/etc/laravel-schedule.env
CRON_FILE=/etc/cron.d/laravel-scheduler
LOG_FILE=/var/www/html/storage/logs/scheduler-cron.log

chmod +x /var/www/html/bin/run-schedule.sh

printenv | grep -E '^(APP_|DB_|REDIS_|QUEUE_|CACHE_|PRODUCT_SCRAPER_|HORIZON_)' > "$ENV_FILE"
chmod 600 "$ENV_FILE"
chown root:root "$ENV_FILE" 2>/dev/null || true

cat > "$CRON_FILE" <<'EOF'
SHELL=/bin/sh
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
* * * * * root /var/www/html/bin/run-schedule.sh >> /var/www/html/storage/logs/scheduler-cron.log 2>&1
EOF
chmod 0644 "$CRON_FILE"

touch "$LOG_FILE"
chown www-data:www-data "$LOG_FILE" 2>/dev/null || true
