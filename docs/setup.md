# Development setup

## Requirements

- PHP 8.3 with the `intl`, `mbstring`, `mysqli` (or `sqlite3`), `json` and `xml` extensions
- Composer 2
- MySQL 8.4 LTS (SQLite 3 is fine for a quick local copy)

No Node.js build step is needed. Bootstrap is stored in
`public/assets/vendor/bootstrap-5.3.8/`.

## Steps

1. Install PHP dependencies:

   ```bash
   composer install
   ```

2. Create the configuration file and fill in the database settings
   (see [configuration.md](configuration.md)):

   ```bash
   cp env .env
   ```

   For a quick SQLite copy, set:

   ```ini
   database.default.DBDriver = SQLite3
   database.default.database = mofaic_dev.db
   ```

   For MySQL, create the database first:

   ```sql
   CREATE DATABASE mofaic_web CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'mofaic_web'@'localhost' IDENTIFIED BY '<choose a password>';
   GRANT ALL ON mofaic_web.* TO 'mofaic_web'@'localhost';
   ```

3. Generate the encryption key:

   ```bash
   php spark key:generate
   ```

4. Create the tables (application and Shield):

   ```bash
   php spark migrate --all
   ```

5. Optional: load demonstration accounts and departments. This refuses to run
   outside `development` and `testing`.

   ```bash
   php spark db:seed StructureSeeder
   php spark db:seed DemoSeeder
   ```

   It creates `demo-superadmin@example.invalid`, `demo-ministryadmin@example.invalid`,
   `demo-editor@example.invalid` and `demo-readonly@example.invalid`. The password
   is `DEMO_PASSWORD` from `.env`, or a random one printed once.

6. Start the development server:

   ```bash
   php spark serve
   ```

   Open http://localhost:8080/ (redirects to `/en`).

## Signing in during development

Every staff sign-in asks for a six-digit code sent by email (proposed default
PD-01). Without a working mail server in development, read the code from the
database after requesting it:

```bash
php spark db:table auth_identities --limit-rows 5
```

The code is in the `secret` column of the newest `email_2fa` row.

## Creating a real staff account

Use Shield's commands. Never reuse demo accounts outside development.

```bash
php spark shield:user create -n "<username>" -e "<email>"
php spark shield:user addgroup -n "<username>" -g ministryadmin
```

Groups: `superadmin`, `ministryadmin`, `editor`, `readonly`. See
[permissions.md](permissions.md).

## Rollback

```bash
php spark migrate:rollback          # undo the last batch
php spark migrate:refresh --all     # development only: drop and recreate everything
```
