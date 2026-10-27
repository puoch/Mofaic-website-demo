# MOFAIC WEB

Public website and staff administration area for the Ministry of Foreign
Affairs & International Cooperation (MFA&IC), Republic of South Sudan.

> **Status: Gate 5 production readiness, development only. Not ready for the
> live launch:** `php spark golive:check` lists what still blocks it. This is not an
> official website. It holds no Ministry content, and no Ministry data has been
> entered. The page structure exists as empty drafts that Ministry staff fill in.
> Public forms stay closed until MOFAIC's approved privacy notice is entered.
> Demonstration records are fictional and labelled `DEMO, NOT OFFICIAL`.

Built with PHP 8.3, CodeIgniter 4.7, CodeIgniter Shield 1.4, MySQL 8.4 and
Bootstrap 5.3. It runs entirely on servers MOFAIC controls. Development and
staging run on a VPS, and the live site runs in MOFAIC's on-premises data
centre.

## Quick start (development)

```bash
composer install
cp env .env                      # then edit the database settings
php spark key:generate
php spark migrate --all
php spark db:seed StructureSeeder  # empty page structure; safe everywhere
php spark db:seed DemoSeeder       # optional, development only
php spark serve                  # http://localhost:8080/en
```

Staff sign-in is at `/login` and the administration area at `/admin`.
Publishing is at `/admin/publishing`.
Full instructions: [docs/setup.md](docs/setup.md).

## Tests

```bash
composer test
```

See [docs/testing.md](docs/testing.md) for running the tests against MySQL, and
[docs/accessibility.md](docs/accessibility.md) for the browser checks.

## Documentation

| File | What it covers |
| --- | --- |
| [docs/setup.md](docs/setup.md) | Installing a development copy |
| [docs/configuration.md](docs/configuration.md) | Every `.env` setting |
| [docs/versions.md](docs/versions.md) | Supported and pinned versions |
| [docs/architecture.md](docs/architecture.md) | Modules, data and request flow |
| [docs/permissions.md](docs/permissions.md) | Roles, permissions and department scope |
| [docs/deployment.md](docs/deployment.md) | VPS staging and on-premises production, backups, the go-live check |
| [docs/shared-hosting.md](docs/shared-hosting.md) | Running an early test copy on shared hosting |
| [docs/infinityfree.md](docs/infinityfree.md) | Step by step: the InfinityFree test copy, deployed from GitHub |
| [docs/operations.md](docs/operations.md) | Runbook, access review, offboarding, capacity, training |
| [docs/accessibility.md](docs/accessibility.md) | WCAG 2.2 AA checks, results and what still needs a person |
| [docs/testing.md](docs/testing.md) | Test suites and how to run them |
| [docs/decision-log.md](docs/decision-log.md) | Confirmed, proposed and deferred decisions |
| [docs/requirements-brief-v2.4.md](docs/requirements-brief-v2.4.md) | The requirements brief with those decisions written in, for any developer or AI tool |

The requirements baseline is the MOFAIC WEB Requirements & Architecture Brief,
[version 2.4](docs/requirements-brief-v2.4.md) (27 October 2026), which writes
the sponsor's decisions into version 2.3 (26 October 2026).
