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
| Scheduled tasks | cron, for queued email from Gate 4 |

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

    client_max_body_size 25m;

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

# Back up the database before migrating
mysqldump --single-transaction mofaic_web > /srv/mofaic-web/backups/pre-<date>.sql

php spark migrate --all
php spark optimize
ln -sfn /srv/mofaic-web/releases/<date> /srv/mofaic-web/current
sudo systemctl reload php8.3-fpm
curl -fsS https://<approved-domain>/health
```

Never run `db:seed DemoSeeder` on staging or the live site. It refuses to run in
`production`.

## Rolling back

1. Point `current` at the previous release and reload PHP-FPM.
2. If the release ran migrations, restore the pre-release database dump, or run
   `php spark migrate:rollback` from the new release folder before switching.
3. Check `/health` and sign in to `/admin`.

## Backups (to be agreed)

Back up the database and `writable/uploads` from Gate 3 onwards. How often,
where backups are kept off-site, and recovery time targets are open (brief
section 6, item 9). Gate 5 includes a restore drill with recorded evidence.

## Monitoring

`GET /health` returns `200 {"status":"ok","database":"ok"}`, or `503` if the
database is unreachable. It reveals no version or path information.
Application logs are in `writable/logs/`.
