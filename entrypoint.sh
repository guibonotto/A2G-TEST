#!/bin/sh
# Brings the schema up to date before the server accepts traffic.
#
# The image is rebuilt on every deploy and nothing else in it runs Artisan, so
# without this the container boots against whatever schema the database happens
# to be on. Any failure here aborts the boot: serving the application against a
# schema it was not written for is worse than not serving it at all.
set -e

database=''

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    database="${DB_DATABASE:-/app/database/database.sqlite}"

    # On a fresh volume the file does not exist yet and SQLite will not create
    # it on its own.
    if [ ! -f "$database" ]; then
        echo "entrypoint: creating the database at $database"
        mkdir -p "$(dirname "$database")"
        touch "$database"
    fi
fi

echo 'entrypoint: running migrations'
php artisan migrate --force

# Artisan runs as root here, so the write-ahead log files it leaves behind
# would otherwise belong to root and the server could not write to them.
if [ -n "$database" ]; then
    chown -R www-data:www-data "$(dirname "$database")"
fi

# Hand over to the base image's entrypoint so its own setup still runs: it is
# the one that turns the arguments below into `frankenphp run ...`.
if command -v docker-php-entrypoint >/dev/null 2>&1; then
    exec docker-php-entrypoint "$@"
fi

exec frankenphp run "$@"
