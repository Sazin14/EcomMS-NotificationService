#!/bin/sh
set -eu

cd /var/www/html

mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    DB_FILE="${DB_DATABASE:-database/database.sqlite}"

    case "$DB_FILE" in
        /*) ;;
        *) DB_FILE="/var/www/html/$DB_FILE" ;;
    esac

    mkdir -p "$(dirname "$DB_FILE")"
    touch "$DB_FILE"
    chown www-data:www-data "$DB_FILE"
fi

chown -R www-data:www-data storage bootstrap/cache

php artisan package:discover --ansi

case "${1:-api}" in
    worker)
        SOURCE="${NOTIFICATION_SOURCE:-order}"

        case "$SOURCE" in
            inventory|order|auth) ;;
            *)
                echo "Invalid NOTIFICATION_SOURCE: $SOURCE" >&2
                echo "Expected inventory, order, or auth." >&2
                exit 2
                ;;
        esac

        exec su -s /bin/sh www-data -c \
            "exec php artisan notification:consume $SOURCE"
        ;;
    api)
        exec supervisord -c /etc/supervisord.conf
        ;;
    *)
        exec "$@"
        ;;
esac
