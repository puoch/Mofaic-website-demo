#!/usr/bin/env bash
# Restore drill: restores the latest backup into a scratch database and a
# scratch uploads folder, compares them with the source, writes the evidence
# to a Markdown file and removes the scratch copies. The live site is not
# touched. Run it straight after scripts/backup.sh, so the source has not
# changed in between; run it at least monthly and keep the evidence files.
#
#   scripts/restore-drill.sh [backup folder]
#
# Settings: APP_DIR, BACKUP_DIR, DB_NAME, MYSQL_DEFAULTS as for backup.sh (the
# account also needs CREATE and DROP on DRILL_DB), plus
#   DRILL_DB   scratch database          default mofaic_restore_drill
#   EVIDENCE   evidence file             default BACKUP_DIR/drill-<UTC time>.md

set -euo pipefail
umask 077

APP_DIR="${APP_DIR:-/srv/mofaic-web/current}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/mofaic-web}"
DB_NAME="${DB_NAME:-mofaic_web}"
MYSQL_DEFAULTS="${MYSQL_DEFAULTS:-/etc/mofaic-web/backup.cnf}"
DRILL_DB="${DRILL_DB:-mofaic_restore_drill}"
stamp="$(date -u +%Y%m%dT%H%M%SZ)"
EVIDENCE="${EVIDENCE:-$BACKUP_DIR/drill-$stamp.md}"

[ "$DRILL_DB" != "$DB_NAME" ] || { echo "DRILL_DB must not be the live database" >&2; exit 1; }

backup="${1:-$(ls -1d "$BACKUP_DIR"/20*Z 2>/dev/null | sort | tail -1)}"
[ -n "$backup" ] && [ -f "$backup/MANIFEST" ] || { echo "no complete backup found" >&2; exit 1; }

scratch="$(mktemp -d)"
cleanup() {
    rm -rf "$scratch"
    mysql --defaults-extra-file="$MYSQL_DEFAULTS" -e "DROP DATABASE IF EXISTS \`$DRILL_DB\`"
}
trap cleanup EXIT

sql() { mysql --defaults-extra-file="$MYSQL_DEFAULTS" -N -B -e "$1"; }

started=$(date +%s)
mysql --defaults-extra-file="$MYSQL_DEFAULTS" -e "DROP DATABASE IF EXISTS \`$DRILL_DB\`"
MYSQL_DEFAULTS="$MYSQL_DEFAULTS" "$(dirname "$0")/restore.sh" "$backup" "$DRILL_DB" "$scratch" --yes > "$scratch.log" 2>&1 || { cat "$scratch.log" >&2; rm -f "$scratch.log"; exit 1; }
rm -f "$scratch.log"
seconds=$(( $(date +%s) - started ))

result=PASSED
rows=""
for table in $(sql "SELECT table_name FROM information_schema.tables WHERE table_schema = '$DB_NAME' ORDER BY table_name"); do
    source_count=$(sql "SELECT COUNT(*) FROM \`$DB_NAME\`.\`$table\`")
    restored_count=$(sql "SELECT COUNT(*) FROM \`$DRILL_DB\`.\`$table\`" 2>/dev/null || echo missing)
    # CHECKSUM TABLE compares contents, not only the number of rows.
    source_sum=$(sql "CHECKSUM TABLE \`$DB_NAME\`.\`$table\`" | cut -f2)
    restored_sum=$(sql "CHECKSUM TABLE \`$DRILL_DB\`.\`$table\`" 2>/dev/null | cut -f2 || echo missing)
    mark=same
    if [ "$source_count" != "$restored_count" ] || [ "$source_sum" != "$restored_sum" ]; then mark=DIFFERENT; result=FAILED; fi
    rows+="| $table | $source_count | $restored_count | $mark |"$'\n'
done

source_files=$(cd "$APP_DIR/public" && find uploads -type f -print0 | sort -z | xargs -0 sha256sum | sha256sum | cut -d' ' -f1)
restored_files=$(cd "$scratch" && find uploads -type f -print0 | sort -z | xargs -0 sha256sum | sha256sum | cut -d' ' -f1)
file_count=$(cd "$scratch" && find uploads -type f | wc -l)
files_mark=same
if [ "$source_files" != "$restored_files" ]; then files_mark=DIFFERENT; result=FAILED; fi

{
    echo "# Restore drill $stamp: $result"
    echo
    echo "- Backup: \`$backup\`"
    grep '^# app commit' "$backup/MANIFEST" | sed 's/^# /- /'
    echo "- Checksums in MANIFEST: verified"
    echo "- Restored into scratch database \`$DRILL_DB\` (removed afterwards)"
    echo "- Restore time: $seconds seconds"
    echo "- Host: $(hostname), MySQL $(sql 'SELECT VERSION()')"
    echo
    echo "| Table | Rows in $DB_NAME | Rows restored | Rows and contents |"
    echo "|---|---|---|---|"
    printf '%s' "$rows"
    echo
    echo "Uploaded files: $file_count restored, contents $files_mark as $APP_DIR/public/uploads."
    echo
    echo "A difference means the source changed after the backup or the restore is broken. Repeat straight after a backup before concluding."
} > "$EVIDENCE"

echo "restore drill $result; evidence in $EVIDENCE"
[ "$result" = PASSED ]
