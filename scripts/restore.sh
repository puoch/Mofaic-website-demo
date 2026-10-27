#!/usr/bin/env bash
# Restores a MOFAIC WEB backup made by scripts/backup.sh.
#
#   scripts/restore.sh <backup folder> <database> [uploads parent folder] --yes
#
# Tables in the backup replace tables of the same name in <database>. Restore into a new, empty database
# first (see scripts/restore-drill.sh), then switch the site to it, so a bad
# backup cannot destroy the live data. Uploads are unpacked into
# "<uploads parent folder>/uploads" when that argument is given. The env file
# is not copied anywhere: compare it with the server's .env by hand.
#
# Uses the MySQL option file in MYSQL_DEFAULTS (default /etc/mofaic-web/restore.cnf).

set -euo pipefail

usage() { echo "usage: $0 <backup folder> <database> [uploads parent folder] --yes" >&2; exit 2; }

[ "$#" -ge 3 ] || usage
[ "${!#}" = "--yes" ] || usage

backup="$1"
db="$2"
uploads_parent=""
[ "$#" -eq 4 ] && uploads_parent="$3"
MYSQL_DEFAULTS="${MYSQL_DEFAULTS:-/etc/mofaic-web/restore.cnf}"

[ -f "$backup/MANIFEST" ] || { echo "no MANIFEST in $backup: the backup is incomplete" >&2; exit 1; }
[[ "$db" =~ ^[A-Za-z0-9_]+$ ]] || { echo "database name may use letters, digits and _ only" >&2; exit 1; }

echo "checking files against MANIFEST"
(cd "$backup" && grep -v '^#' MANIFEST | sha256sum -c --quiet -)

echo "restoring database into $db"
mysql --defaults-extra-file="$MYSQL_DEFAULTS" -e "CREATE DATABASE IF NOT EXISTS \`$db\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
gunzip -c "$backup/database.sql.gz" | mysql --defaults-extra-file="$MYSQL_DEFAULTS" --default-character-set=utf8mb4 "$db"

if [ -n "$uploads_parent" ]; then
    echo "restoring uploads into $uploads_parent/uploads"
    mkdir -p "$uploads_parent"
    tar -C "$uploads_parent" -xzf "$backup/uploads.tar.gz"
fi

echo "restore finished"
