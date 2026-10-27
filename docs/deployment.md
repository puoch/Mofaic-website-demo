# Deployment

Decided 26 October 2026: **development and staging run on a VPS. The live site
runs in MOFAIC's on-premises data centre.** The application uses no
provider-specific services, so the same steps work in both places.

The on-premises hardware, network edge, mail relay and disaster recovery
arrangements are still open (brief section 6, items 7, 9 and 10). Anything
below marked *proposed* needs MOFAIC approval.

## Server baseline (proposed)

| Item | Proposal |
| --- | --- |
| Operating system | Ubuntu Server 24.04 LTS |
| Web server | Nginx with PHP-FPM 8.3 |
| Database | MySQL 8.4 LTS on the same server at first, separate later if load needs it |
| TLS | Certificate for the approved domain; HTTP redirects to HTTPS |
| Mail | SMTP relay. Staff sign-in depends on it |
| Scheduled tasks | cron runs `php spark mail:send` every minute (queued email, PD-10) |

Only `public/` is served by the web server. `app/`, `vendor/` and `writable/`
must not be reachable from the web.

### Example Nginx site

```nginx
server {
    listen 443 ssl http2;
    server_name <approved-domain>;
    root /srv/mofaic-web/current/public;
    index index.php;

    ssl_certificate     /etc/ssl/<approved-domain>/fullchain.pem;
    ssl_certificate_key /etc/ssl/<approved-domain>/privkey.pem;

    # Browsers use HTTPS only for this domain. Nginx drops server-level
    # add_header lines in a location that has its own, so it is repeated there.
    add_header Strict-Transport-Security "max-age=31536000" always;

    # Must allow the largest upload in Config\Media (PD-12, 200 MB video).
    # Set upload_max_filesize and post_max_size in php.ini to match.
    client_max_body_size 210m;

    # Compress text; Bootstrap's CSS and JS shrink from 312 KB to about 55 KB.
    gzip on;
    gzip_types text/css application/javascript image/svg+xml application/json;

    # Let browsers keep stylesheets and scripts between pages.
    location ^~ /assets/ {
        expires 1d;
        add_header X-Content-Type-Options nosniff always;
        add_header Strict-Transport-Security "max-age=31536000" always;
    }

    # Uploaded files are never run as code.
    location ^~ /uploads/ {
        location ~ \.php$ { deny all; }
        add_header X-Content-Type-Options nosniff always;
        add_header Strict-Transport-Security "max-age=31536000" always;
    }

    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ /\. { deny all; }
}

server {
    listen 80;
    server_name <approved-domain>;
    return 301 https://$host$request_uri;
}
```

## Releasing a version

Releases go into dated folders, and `current` points at the active one, so a
rollback means switching the link back.

```bash
cd /srv/mofaic-web
git clone --depth 1 --branch <tag> <repository-url> releases/<date>
cd releases/<date>
composer install --no-dev --optimize-autoloader --no-interaction
ln -s /srv/mofaic-web/shared/.env .env
rm -rf writable && ln -s /srv/mofaic-web/shared/writable writable
rm -rf public/uploads && ln -s /srv/mofaic-web/shared/uploads public/uploads

# Back up before migrating (see Backups below)
APP_DIR=/srv/mofaic-web/current /srv/mofaic-web/current/scripts/backup.sh

php spark migrate --all
php spark db:seed StructureSeeder   # adds any missing structure pages; never overwrites
php spark optimize
ln -sfn /srv/mofaic-web/releases/<date> /srv/mofaic-web/current
sudo systemctl reload php8.3-fpm
curl -fsS https://<approved-domain>/health
```

Add the mail task once per server (as the user that runs PHP-FPM):

```bash
* * * * * cd /srv/mofaic-web/current && php spark mail:send --limit 50 >> /srv/mofaic-web/shared/writable/logs/mail.log 2>&1
```

Before opening the public forms on the live site, `encryption.key` must be set
(form tokens and unsubscribe links are signed with it), and Ministry
Administrators must enter the approved privacy notice and voluntary
registration statement under **Content > Fixed text blocks**.

Never run `db:seed DemoSeeder` on staging or the live site. It refuses to run in
`production`.

## Rolling back

1. Point `current` at the previous release and reload PHP-FPM.
2. If the release ran migrations, run `php spark migrate:rollback` from the new
   release folder before switching, or restore the pre-release backup with
   `scripts/restore.sh` (see Backups).
3. Check `/health` and sign in to `/admin`.

## Before the live launch

Run this on the live server. It lists every remaining blocker: settings in
`.env`, demonstration data, Ministry text that is still missing, backups, and
every proposal and deferred item in [decision-log.md](decision-log.md) that
MOFAIC has not settled. It exits with status 1 while anything blocks.

```bash
php spark golive:check                  # everything
php spark golive:check --only-blockers  # failed and warning lines only
```

A decision stops blocking only when it is added to `app/Config/GoLive.php`
with where the Ministry's decision is recorded. Nothing else marks it done.

Also set in the PHP-FPM `php.ini` (the check reads the command-line PHP
settings, which can differ): `display_errors = Off`, `expose_php = Off`.
On the Gate 5 production-mode test copy the `X-Powered-By: PHP/8.3.6` header
was still sent because `expose_php` was on.

## Backups (proposal G5-03)

Brief section 6 item 9 (backup and recovery targets) is still open, so the
schedule below is a proposal.

`scripts/backup.sh` writes a dated folder with the database (`mysqldump
--single-transaction`, so the site stays up), the uploaded files and a copy of
`.env`, plus a `MANIFEST` of checksums written last. `.env` holds the database
password and `encryption.key`, so the backup folder is created readable by
its owner only. Keep the backups on another disk and copy them off the server:
a backup on the same disk does not survive losing that disk.

Create a MySQL account for backups with only what it needs, and an option file
so the password is not on the command line:

```sql
CREATE USER 'mofaic_backup'@'localhost' IDENTIFIED BY '<password>';
GRANT SELECT, SHOW VIEW, TRIGGER, LOCK TABLES, EVENT, PROCESS ON *.* TO 'mofaic_backup'@'localhost';
-- for restore drills only:
GRANT ALL ON mofaic_restore_drill.* TO 'mofaic_backup'@'localhost';
```

```ini
# /etc/mofaic-web/backup.cnf, mode 600, owned by the backup user
[client]
user=mofaic_backup
password=<password>
```

Proposed schedule (cron of the backup user):

```bash
30 1 * * * APP_DIR=/srv/mofaic-web/current /srv/mofaic-web/current/scripts/backup.sh >> /var/log/mofaic-web-backup.log 2>&1
# monthly drill, straight after a fresh backup so the live data has not moved on
0 3 1 * *  cd /srv/mofaic-web/current && APP_DIR=$PWD scripts/backup.sh && APP_DIR=$PWD scripts/restore-drill.sh >> /var/log/mofaic-web-backup.log 2>&1
```

Backups older than 14 days are removed (`KEEP_DAYS`). A submission that
arrives between the backup and the comparison shows as a difference; repeat
the drill before treating that as a broken backup. Each restore drill
restores the newest backup into a scratch database, compares every table's row
count and checksum and every uploaded file with the live copies, writes the
result to `drill-<time>.md` in the backup folder, and removes the scratch copy.
Keep those files as evidence. The Gate 5 drill is in
[evidence/gate-5/restore-drill.md](evidence/gate-5/restore-drill.md).

### Restoring after a failure

1. Put the site into maintenance (stop PHP-FPM or serve a static notice).
2. Restore into a **new** database first and check it:
   `MYSQL_DEFAULTS=/etc/mofaic-web/restore.cnf scripts/restore.sh <backup> mofaic_web_restored /srv/mofaic-web/shared --yes`
   (the third argument restores `uploads` into that folder).
   The script refuses a backup without a `MANIFEST` or with a changed file.
3. Point `database.default.database` in `.env` at the restored database, or
   restore again into `mofaic_web` once the copy is confirmed.
4. Compare the backup's `env` file with the server's `.env`. Restore
   `encryption.key` from it if the server's copy was lost; otherwise emailed
   newsletter links stop working.
5. Run `php spark migrate:status`, open `/health`, sign in to `/admin`, and
   check the newest content and submissions.

## Monitoring

`GET /health` returns `200 {"status":"ok","database":"ok"}`, or `503` if the
database is unreachable. It reveals no version or path information.
Application logs are in `writable/logs/`.
