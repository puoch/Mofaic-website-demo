# Test copy on InfinityFree

The sponsor chose InfinityFree's free tier for an early test copy, with code
going from GitHub to the host (27 October 2026). This is a **test and
demonstration copy only**. The recorded hosting decision has not changed:
development and staging on a VPS, the live site in MOFAIC's on-premises data
centre ([deployment.md](deployment.md)).

## Ground rules

- No real Ministry content, contacts or personal data. Enter test content by
  hand, labelled as test content.
- Leave the privacy notice block empty, so the public forms stay closed (G4-01).
- Use passwords made only for this copy. Do not reuse them anywhere.

## What InfinityFree's free tier allows

Checked on 27 October 2026 against InfinityFree's own site and forum. These can
change, so check again before relying on them.

| Limit | Effect on this site | What the build does |
| --- | --- | --- |
| PHP 8.4, MySQL 8.0 or MariaDB 11.4 ([infinityfree.com](https://www.infinityfree.com/)) | Choose MySQL 8.0 if asked. MariaDB is not tested | CI now also tests PHP 8.4. The suite passes on MySQL 8.0 |
| The web root is always `<domain>/htdocs` (the `htdocs` folder at the FTP root is not served); it cannot be pointed at `public/` ([forum guide](https://forum.infinityfree.com/t/how-to-install-a-laravel-site-on-infinityfree/118578)) | `.env` and the code sit inside the web root | The root `.htaccess` sends every request into `public/`; `.env`, `vendor/` and the rest return 404 |
| No SSH, no Composer | `php spark` cannot run | The bundle includes `vendor/`. `/maintenance` runs migrations and creates staff accounts |
| No cron ([announcement](https://forum.infinityfree.com/t/cron-job-feature-is-now-disabled/80495)); outside services may not trigger it either | `mail:send` never runs | `hosting.sendMailInRequest` sends each message when it is queued; staff pages send waiting newsletter mail a few at a time |
| `mail()` restricted; use an outside SMTP service ([article](https://forum.infinityfree.com/t/emails-from-my-website-arent-working/49242)) | Staff sign-in codes are emailed (PD-01), so **without SMTP nobody can sign in** | Set an outside SMTP service in `.env` |
| 10 MB per file, cannot be raised ([forum](https://forum.infinityfree.com/t/file-upload-limit/92605/4)) | PD-12 allows PDFs, audio and video up to 200 MB | `hosting.maxUploadMegabytes = 10` lowers every upload limit; link audio and video instead |
| PHP files over 1 MB are reportedly dropped | Composer's class map could be one | `scripts/build-bundle.sh` stops if any PHP file is over 1 MB or any file over 10 MB |
| A browser check (JavaScript and cookie) in front of every request, cannot be switched off ([article](https://forum.infinityfree.com/t/browser-security-system-features-and-limitations/49353)) | `/health` monitoring, `curl` and scripts get a challenge page | Nothing to change for visitors. The go-live check's own request warns instead of failing |
| FTP only (no SFTP), FTP over TLS on `ftpupload.net` port 21; the certificate chain is incomplete ([forum](https://forum.infinityfree.com/t/ftp-secure-transfer-with-curl-isnt-working/71316)) | | The deploy workflow uses FTP over TLS without checking the certificate (see below) |
| No backups promised | | Export the database from phpMyAdmin if you want to keep test content |

## Security on the free tier

- **`.env` is inside the web root.** Only the root `.htaccess` keeps it private.
  After every upload, open `https://<your-site>/.env` and
  `https://<your-site>/composer.json`. Both must say 404.
- **FTP certificate.** The upload is encrypted, but because InfinityFree's
  certificate chain is incomplete, the workflow does not verify who it is
  talking to (`security: loose`). Someone who could intercept GitHub's
  connection could read the FTP password. Acceptable for a test copy; it is one
  more reason never to reuse that password.
- **`/maintenance`** can create Super Administrator accounts. It needs the
  token from `.env`, allows 10 tries an hour per address, and records every use
  in the audit log. Remove the token from `.env` as soon as you have finished.
- **Shared server.** Other customers share the machine and the database
  server. You cannot set PHP options, and the host can suspend the account.

## One-time setup

1. **Hosting account.** Sign up at InfinityFree and create a hosting account
   with a free subdomain. Keep the PHP version at 8.4.
2. **Database.** In the control panel, open *MySQL Databases* and create one.
   Write down the host (it is not `localhost`; it looks like
   `sqlNNN.infinityfree.com`), the database name, the user and the password.
3. **HTTPS.** In the client area, request the free SSL certificate for the
   subdomain and wait until it is active.
4. **Email.** Create a free account with an SMTP service (InfinityFree's article
   names SMTP2GO, Brevo, MailerSend and Resend). Verify the sending address
   there. Write down the SMTP host, port, user and password or key.
5. **GitHub secrets.** In the repository on GitHub: *Settings*, *Secrets and
   variables*, *Actions*, *New repository secret*. Add three, using the *FTP
   details* from InfinityFree's client area:
   - `INFINITYFREE_FTP_SERVER` (for example `ftpupload.net`)
   - `INFINITYFREE_FTP_USERNAME`
   - `INFINITYFREE_FTP_PASSWORD`
6. **First upload.** In the repository: *Actions*, *Deploy test copy to
   InfinityFree*, *Run workflow*, branch `main`, with the site folder set to
   your domain. It runs the tests, builds the bundle and uploads it to
   `<domain>/htdocs`, the folder named after the domain in the FTP root (not
   the `htdocs` folder beside it, which no domain serves). The first upload takes a while (about
   2,800 files).
7. **`.env`.** In InfinityFree's file manager, create `<domain>/htdocs/.env` with the
   lines below, filled in. Make the two random values on your own computer:
   `openssl rand -hex 32` for the encryption key and `openssl rand -hex 24` for
   the maintenance token (or `php -r "echo bin2hex(random_bytes(32));"`).

   ```ini
   CI_ENVIRONMENT = production
   app.baseURL = 'https://<your-subdomain>/'
   app.forceGlobalSecureRequests = true
   cookie.secure = true
   encryption.key = hex2bin:<64 characters from openssl rand -hex 32>

   database.default.hostname = <sqlNNN.infinityfree.com>
   database.default.database = <database name>
   database.default.username = <database user>
   database.default.password = <database password>
   database.default.DBDriver = MySQLi
   database.default.port = 3306
   database.default.charset = utf8mb4
   database.default.DBCollat = utf8mb4_unicode_ci

   email.fromEmail = <the address you verified with the SMTP service>
   email.fromName = 'MOFAIC WEB (test copy)'
   email.protocol = smtp
   email.SMTPHost = <SMTP host>
   email.SMTPPort = 587
   email.SMTPCrypto = tls
   email.SMTPUser = <SMTP user>
   email.SMTPPass = <SMTP password or key>

   assets.bootstrapSource = cdn
   hosting.maintenanceToken = <48 characters from openssl rand -hex 24>
   hosting.sendMailInRequest = true
   hosting.maxUploadMegabytes = 10
   ```

   Replace each `<...>` **including the angle brackets**: the brackets left in
   place broke the token, the database password and the encryption key on
   the first test copy. Write the database password in single quotes if it
   has special characters. The file manager cannot open or download `.env`,
   so write it in Notepad as `env.txt`, upload it, and rename it to `.env`.

   Set `app.forceGlobalSecureRequests` and `cookie.secure` only once HTTPS
   works, or you will be redirected to a page that does not load. If pages
   then keep redirecting, remove those two lines and tell the developer: the
   host may not pass HTTPS through to PHP.
8. **Database tables and first account.** Open `https://<your-subdomain>/maintenance`,
   enter the token, and press *Run migrations and add structure pages*. Then
   create your own account with the role *Super Administrator* and your real
   email address. The page also shows the go-live check; a test copy is not
   meant to pass it.
9. **Sign in** at `https://<your-subdomain>/login`. The sign-in code arrives by
   email. If it does not, check the SMTP settings. With Brevo, also turn off
   *Security*, *Authorized IPs* blocking: otherwise Brevo answers
   `525 5.7.1 Unauthorized IP address` and sign-in stops with an error page,
   and InfinityFree does not send from one fixed address.
10. **Close the setup page.** Delete the `hosting.maintenanceToken` line from
    `.env`. `/maintenance` then gives 404.
11. **Check privacy.** `https://<your-subdomain>/.env` and `/composer.json` must
    both give 404.

## When something goes wrong

InfinityFree replaces the site's own 404 and 500 pages with its own, so an
application error looks like a hosting error. Look in
`<domain>/htdocs/writable/logs/` for the day's log (it opens in the file
manager) and read the newest `CRITICAL` line.

| What you see | Usually |
|---|---|
| InfinityFree 404 for every address, even a plain text file | Files are in the `htdocs` at the FTP root instead of `<domain>/htdocs` |
| `/maintenance` gives 404 | No `hosting.maintenanceToken` of 32 or more characters in `.env` |
| "That is not the maintenance token" | The page says both lengths; 2 extra characters are leftover `<` `>`. If the key appears twice in `.env`, the first one counts |
| "Access denied for user" in the log | Wrong database password in `.env` (it is the hosting account's password) |
| "hex2bin(): Hexadecimal input string must have an even length" | `encryption.key` still has `<` `>` or is cut short |
| "The action you requested is not allowed" | A form was sent twice or from an older tab; close other tabs of the site and send once |
| "Unauthorized IP address" in the log | Brevo's Authorized IPs blocking (step 9) |

## Updating the test copy

1. Merge the change on GitHub, then run *Deploy test copy to InfinityFree*
   again. Only changed files are uploaded; `.env`, uploaded media, logs and
   sessions on the server are left alone.
2. If the change has a database migration, put `hosting.maintenanceToken`
   back into `.env`, open `/maintenance`, run migrations, and remove the token
   again.

## Without GitHub Actions

Run `scripts/build-bundle.sh` on a computer with PHP 8.3 or later and Composer.
Upload the contents of `build/bundle/` into `<domain>/htdocs` with an FTP program
(FileZilla: *FTP over explicit TLS*, host `ftpupload.net`, port 21). Then follow
steps 7 to 11.

## What was tested

On 27 October 2026, the bundle from `scripts/build-bundle.sh` ran on Apache
2.4 with `.htaccess` switched on, PHP 8.3 and MySQL 8.0, with the document root
on the project folder, as on InfinityFree. Starting from an empty database,
`/maintenance` ran the migrations and created a Super Administrator in a real
browser. Afterwards the home page, the sign-in page and the missions pages
loaded, and `.env`, `composer.json` and `vendor/` gave 404. Not tested: a real
InfinityFree account, its browser check, PHP 8.4 (CI runs it from this
change), email through an outside SMTP service, and the GitHub upload itself.
