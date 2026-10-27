<!-- Written by scripts/restore-drill.sh on the Gate 5 test machine. Source: a copy of the application on MySQL 8.0.46 (the proposed live version is 8.4) with the structure pages, demonstration data, one demo inquiry and three files in uploads. <drill> stands for the scratch folder. -->

# Restore drill 20261027T083949Z: PASSED

- Backup: `<drill>/backups/20261027T083949Z`
- app commit: 1956aab, plus the uncommitted Gate 5 changes (the drill ran before the Gate 5 commit)
- Checksums in MANIFEST: verified
- Restored into scratch database `mofaic_restore_drill` (removed afterwards)
- Restore time: 1 seconds
- Host: vm, MySQL 8.0.46-0ubuntu0.24.04.4

| Table | Rows in mofaic_drill_source | Rows restored | Rows and contents |
|---|---|---|---|
| archive_items | 0 | 0 | same |
| article_tag | 1 | 1 | same |
| articles | 5 | 5 | same |
| audit_events | 21 | 21 | same |
| auth_groups_users | 4 | 4 | same |
| auth_identities | 4 | 4 | same |
| auth_logins | 0 | 0 | same |
| auth_permissions_users | 0 | 0 | same |
| auth_remember_tokens | 0 | 0 | same |
| auth_token_logins | 0 | 0 | same |
| content_revisions | 9 | 9 | same |
| countries | 3 | 3 | same |
| department_user | 2 | 2 | same |
| departments | 3 | 3 | same |
| diaspora_contributions | 0 | 0 | same |
| diaspora_country_pages | 1 | 1 | same |
| diaspora_registrations | 0 | 0 | same |
| galleries | 0 | 0 | same |
| inquiries | 1 | 1 | same |
| mail_queue | 0 | 0 | same |
| media | 0 | 0 | same |
| media_links | 0 | 0 | same |
| migrations | 8 | 8 | same |
| mission_coverage | 3 | 3 | same |
| missions | 3 | 3 | same |
| newsletter_sends | 0 | 0 | same |
| newsletter_subscriptions | 0 | 0 | same |
| notification_rules | 3 | 3 | same |
| org_units | 0 | 0 | same |
| pages | 21 | 21 | same |
| quick_links | 0 | 0 | same |
| settings | 0 | 0 | same |
| site_blocks | 5 | 5 | same |
| social_accounts | 0 | 0 | same |
| submission_notes | 0 | 0 | same |
| tags | 1 | 1 | same |
| users | 4 | 4 | same |

Uploaded files: 3 restored, contents same as <drill>/app/public/uploads.

A difference means the source changed after the backup or the restore is broken. Repeat straight after a backup before concluding.

## Other restore cases tried on 27 October 2026

| Case | Result |
| --- | --- |
| A backup file changed after the backup (one byte added to `uploads.tar.gz`) | `restore.sh` refused: checksum failed, nothing restored, exit 1 |
| A backup folder without `MANIFEST` | Refused as incomplete, exit 1 |
| `restore.sh` without `--yes` | Refused, exit 2 |
| Source data changed after the backup | The drill reported `inquiries` as DIFFERENT and exited 1 |
| Disaster: database dropped and uploads deleted, then restored in place | Restored; 21 pages, the inquiry with non-ASCII text (`Juba – Malakal ✓ عربي`) intact, 3 upload files back; `migrate:status` and `golive:check` ran against it |
| `golive:check` with this backup folder configured | "A complete backup from the last 26 hours": PASS |
