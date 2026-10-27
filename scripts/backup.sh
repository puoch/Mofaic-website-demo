#!/usr/bin/env bash
# Backs up MOFAIC WEB: the MySQL database, uploaded media and the .env file.
#
#   sudo -u mofaic-backup scripts/backup.sh
#
# Settings (environment variables):
#   APP_DIR          application folder           default /srv/mofaic-web/current
#   BACKUP_DIR       where backups go             default /var/backups/mofaic-web
#   DB_NAME          database name                default mofaic_web
#   MYSQL_DEFAULTS   MySQL option file with user and password (mode 600), so the
#                    password never appears in the process list
#                                                 default /etc/mofaic-web/backup.cnf
#   KEEP_DAYS        delete backups older than this (proposal G5-03; MOFAIC's
#                    backup and recovery targets are deferred item 9)  default 14
#
# Each run writes BACKUP_DIR/<UTC time>/ with database.sql.gz, uploads.tar.gz,
# env (contains secrets) and MANIFEST. MANIFEST is written last, so a folder
# without it is incomplete; `php spark golive:check` looks for it.
#
# Copy the folder off the server as well (a second site or offline media):
# a backup on the same disk does not survive the loss of that disk.

set -euo pipefail
umask 077

APP_DIR="${APP_DIR:-/srv/mofaic-web/current}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/mofaic-web}"
DB_NAME="${DB_NAME:-mofaic_web}"
MYSQL_DEFAULTS="${MYSQL_DEFAULTS:-/etc/mofaic-web/backup.cnf}"
KEEP_DAYS="${KEEP_DAYS:-14}"

stamp="$(date -u +%Y%m%dT%H%M%SZ)"
target="$BACKUP_DIR/$stamp"
mkdir -p "$target"

fail() {
    echo "backup failed: $*" >&2
    rm -rf "$target"
    exit 1
}
trap 'fail "line $LINENO"' ERR

[ -r "$MYSQL_DEFAULTS" ] || fail "cannot read $MYSQL_DEFAULTS"
[ -d "$APP_DIR/public/uploads" ] || fail "no uploads folder in $APP_DIR"

# A consistent copy of InnoDB tables without locking the site.
mysqldump --defaults-extra-file="$MYSQL_DEFAULTS" \
    --single-transaction --quick --routines --triggers --no-tablespaces \
    --default-character-set=utf8mb4 "$DB_NAME" | gzip -9 > "$target/database.sql.gz"

tar -C "$APP_DIR/public" -czf "$target/uploads.tar.gz" uploads

if [ -f "$APP_DIR/.env" ]; then
    cp "$APP_DIR/.env" "$target/env"
fi

{
    echo "# MOFAIC WEB backup $stamp"
    echo "# database: $DB_NAME"
    echo "# app commit: $(git -C "$APP_DIR" rev-parse --short HEAD 2>/dev/null || echo unknown)"
    echo "# tables:"
    mysql --defaults-extra-file="$MYSQL_DEFAULTS" -N -B -e \
        "SELECT CONCAT('#   ', table_name) FROM information_schema.tables WHERE table_schema = '$DB_NAME' ORDER BY table_name"
    (cd "$target" && sha256sum database.sql.gz uploads.tar.gz $( [ -f env ] && echo env ))
} > "$target/MANIFEST.tmp"
mv "$target/MANIFEST.tmp" "$target/MANIFEST"

trap - ERR

# Remove backups older than KEEP_DAYS. A failed run removes its own folder.
find "$BACKUP_DIR" -mindepth 1 -maxdepth 1 -type d -name '20*Z' -mtime +"$KEEP_DAYS" -exec rm -rf {} +

echo "backup written to $target"
