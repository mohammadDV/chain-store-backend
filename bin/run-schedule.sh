#!/bin/sh
# Run by container cron. Loads env exported at container start so Redis/DB
# hostnames from docker-compose are available (cron has a minimal environment).
set -e
cd /var/www/html

if [ -f /etc/laravel-schedule.env ]; then
  set -a
  # shellcheck disable=SC1091
  . /etc/laravel-schedule.env
  set +a
fi

exec /usr/local/bin/php artisan schedule:run "$@"
