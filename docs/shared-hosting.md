# Testing on shared hosting

The sponsor said on 27 October 2026 that the site will likely be tested on
shared hosting first, and then chose InfinityFree's free tier: see
[infinityfree.md](infinityfree.md) for that host's step-by-step guide. This page covers that test copy only. The recorded
hosting decision has not changed: development and staging on a VPS, the live
site in MOFAIC's on-premises data centre ([deployment.md](deployment.md)).

Do not put real personal data on a shared host. The forms stay closed until
the privacy notice is approved (G4-01), so a test copy collects nothing unless
someone enters that text.

## What was checked

Checked 27 October 2026 on Apache 2.4.58 with mod_php 8.3 and `.htaccess`
files switched on (`AllowOverride All`, as on cPanel hosting), with MySQL
8.0.46, in production mode. This was a copy of a typical shared host, not a
real hosting account. LiteSpeed, PHP-FPM behind Apache, and MariaDB were not
tested.

| Check | Result |
| --- | --- |
| Migrations and `StructureSeeder` on MySQL 8.0 | Work. The full test suite also passes on MySQL 8.0 |
| Document root set to `public/` | Public pages, sign-in, `/admin` and `/health` work |
| Document root set to the project folder | **Before the fix, `.env` (with the database password), `composer.json`, `vendor/` and `docs/` could be downloaded.** The new root `.htaccess` sends every request into `public/`, and all of those now return 404 |
| A `.php` file in `public/uploads/` | **Ran as code on Apache before the fix.** `public/uploads/.htaccess` now refuses it (403). The site itself always gives uploads a random name and a media extension (`MediaService`), so this is a second line of defence |
| `nosniff` on stylesheets, scripts and uploads | Was missing, because Apache serves these without the application. `public/.htaccess` now adds it, plus HSTS on HTTPS |
| `php spark mail:send` from cron, `scripts/backup.sh` with paths in the account | Work. Every path in the backup script can be set (see below) |
| `php spark golive:check` | New line: **Project files are not served by the web server**. It asks the site for `composer.json` and fails if it gets it |

## What the host must offer

| Need | Why |
| --- | --- |
| PHP 8.3 or later, with `intl`, `mbstring`, `mysqli`, `fileinfo`, `json` and `openssl` | CodeIgniter 4.7 and Shield. `fileinfo` checks uploaded file types |
| MySQL 8.0 or 8.4 | Tested. MariaDB is not tested; avoid it, or run the test suite on it first |
| Apache or LiteSpeed reading `.htaccess`, with `mod_rewrite` and `mod_headers` | Clean addresses and the protections above. Nginx-only hosts need the server block from deployment.md instead |
| SSH or the cPanel Terminal | **Needed.** Creating the first staff account (`php spark shield:user create`) asks for the password interactively, although staff accounts can now be managed on Admin > Staff accounts once the first Super Administrator exists. Migrations could run from a one-off cron job, but account setup cannot |
| Cron jobs | `mail:send` sends staff notifications and newsletters (PD-10). Many hosts allow every 5 or 15 minutes, not every minute; email then waits that long. Staff sign-in codes do not wait for cron, because Shield sends them directly |
| SMTP with your sending address | Sign-in codes are emailed (PD-01), so without mail nobody can sign in. Hosts usually limit email per hour; send newsletters with `--limit` |
| HTTPS (AutoSSL or Let's Encrypt) | Set `app.forceGlobalSecureRequests` and `cookie.secure` as for staging |
| Upload limits | PD-12 allows MP4 files up to 200 MB. Shared hosts often stop PHP uploads at 64 or 128 MB; link large videos instead (PD-12 allows an https address) or lower the limit in `Config\Media` for the test copy |

## Setting it up

1. On your own computer, in the project folder, run
   `composer install --no-dev --optimize-autoloader`. Hosts rarely have
   Composer, so `vendor/` is uploaded with the code.
2. Upload the project **outside** `public_html`, for example `~/mofaic`. Leave
   out `.env`, `.git`, `tests/`, and anything in `writable/` apart from its
   folders and `index.html` files.
3. Point the domain or subdomain's document root at `~/mofaic/public`. In
   cPanel: *Domains*, then the domain, then *Document Root*.
   If the host will not let you change it, upload the project into the
   document root folder itself. The root `.htaccess` then sends every request
   into `public/`. This works, but it is the weaker setup: it depends on
   `mod_rewrite` and on `.htaccess` being read.
4. Make a MySQL database and user (utf8mb4) in the control panel.
5. Copy `env` to `.env` and fill it in as for staging (configuration.md).
   Give `.env` mode 600. Set `assets.bootstrapSource` to `cdn` or `local`;
   both work on shared hosting (PD-17).
6. In SSH or the Terminal:

   ```bash
   cd ~/mofaic
   php spark key:generate
   php spark migrate --all
   php spark db:seed StructureSeeder
   php spark shield:user create -n "<username>" -e "<email>"
   php spark shield:user addgroup -n "<username>" -g superadmin
   php spark golive:check
   ```

   If `php` is the wrong version, the control panel names the right binary,
   for example `/opt/cpanel/ea-php83/root/usr/bin/php`. Use the same one in
   cron.
7. Cron jobs (control panel, *Cron Jobs*):

   ```cron
   */5 * * * * cd ~/mofaic && php spark mail:send --limit 50 >> writable/logs/mail.log 2>&1
   30 1 * * *  APP_DIR=~/mofaic BACKUP_DIR=~/backups DB_NAME=<database> MYSQL_DEFAULTS=~/.mofaic-backup.cnf bash ~/mofaic/scripts/backup.sh
   ```

   `~/.mofaic-backup.cnf` holds the database user and password (mode 600, see
   the Backups section of deployment.md). Copy `~/backups` off the host too, or
   rely on the host's own backups for a test copy. To make `golive:check` read
   the right backup folder, set `$backupDirectory` in `Config\GoLive`.
8. Open `https://<test-address>/composer.json`. It must give 404 (or 403).

## Demonstration data

`DemoSeeder` refuses to run in production mode, and a host on the internet
must run in production mode (development mode shows error details to anyone).
So a shared-hosting copy starts empty. Enter test content by hand, clearly
marked as test content, and never as Ministry information.
