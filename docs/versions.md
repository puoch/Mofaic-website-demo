# Versions

Checked 26 October 2026 against Packagist and the npm registry. Versions are
locked in `composer.lock` and by the vendored Bootstrap folder name.

| Component | Version | How it is pinned |
| --- | --- | --- |
| PHP | 8.3 | `composer.json` requires `^8.3`; tests run on 8.3.6 |
| CodeIgniter framework | 4.7.5 | `composer.lock` |
| CodeIgniter Shield | 1.4.1 | `composer.lock` |
| CodeIgniter Settings | 2.4.0 | `composer.lock` (Shield dependency) |
| PHPUnit (development only) | 10.5.66 | `composer.lock` |
| MySQL | 8.4 LTS | CI runs tests on the `mysql:8.4` image. A local check also ran on 8.0.46 |
| Bootstrap | 5.3.8 | `public/assets/vendor/bootstrap-5.3.8/`, with `SHA256SUMS` |

## Why these versions

- **PHP 8.3** is the version the tests run on. It is packaged by Ubuntu 24.04 LTS,
  the proposed server operating system (see [deployment.md](deployment.md)).
- **MySQL 8.4** is MySQL's current long-term support release. The brief names
  MySQL as the database. MariaDB has not been tested.
- **Bootstrap** is copied into the repository rather than loaded from a CDN, so the
  site works without access to outside services.

## Not yet checked

- Each component's support end date against MOFAIC's upgrade policy. That policy
  is still open (brief section 6, item 11).
- The exact PHP and MySQL packages available on the MOFAIC on-premises servers.

## Updating

```bash
composer outdated --direct
composer update codeigniter4/framework codeigniter4/shield --with-dependencies
composer test
```

To update Bootstrap, download the new release's `dist` files into a new
`bootstrap-<version>` folder, regenerate `SHA256SUMS`, and update the path in
`app/Views/partials/head_assets.php` and `app/Views/layouts/public.php`.
