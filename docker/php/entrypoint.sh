#!/bin/sh
set -e

# Runs as root before php-fpm: make sure Laravel's writable directories exist
# and are owned by the runtime user (www-data). On a fresh clone the bind-mounted
# files belong to the host user, so php-fpm (www-data) cannot write the compiled
# views / cache / logs — this fixes it automatically on every start.
mkdir -p \
    storage/framework/views \
    storage/framework/cache \
    storage/framework/sessions \
    storage/logs \
    bootstrap/cache

# Drop any stale compiled route/config cache so a restarted container always uses
# the current routes/config. A leftover route cache (e.g. from a past
# `php artisan route:cache`) would otherwise hide routes added later -> 404.
rm -f bootstrap/cache/routes-v7.php bootstrap/cache/config.php

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

exec "$@"
