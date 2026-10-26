# Testing

```bash
composer test           # all tests, in-memory SQLite
```

To run against MySQL 8.4, as CI does:

```bash
export database_tests_hostname=127.0.0.1
export database_tests_database=mofaic_test
export database_tests_username=mofaic_test
export database_tests_password=<password>
export database_tests_DBDriver=MySQLi
export database_tests_charset=utf8mb4
export database_tests_DBCollat=utf8mb4_unicode_ci
vendor/bin/phpunit --no-coverage
```

Tests create and drop their own tables (prefixed `db_`), so use a database that
holds nothing else.

## What the tests cover

| File | Requirements | What it checks |
| --- | --- | --- |
| `tests/unit/NavigationConfigTest.php` | NAV-01, NAV-02, MIS-01 | Approved navigation order and labels, exact missions hierarchy, ARCHIVES placement by configuration |
| `tests/feature/PublicSiteTest.php` | PRI-04, PRI-07, PRI-09, SEC-02 | All 17 public routes: same fixed navigation, `lang`/`dir` attributes, no assets from other hosts, unsupported language returns 404, security headers, non-production banner |
| `tests/unit/LanguagesTest.php` | PRI-07 | English-only launch, right-to-left by configuration |
| `tests/feature/StaffAccessTest.php` | GOV-01, SEC-01, SEC-04 | No registration or magic link, admin needs sign-in, each role reaches the dashboard, audit log restricted, CSRF rejection, failed sign-in audited without the password, emailed code required |
| `tests/unit/PermissionsTest.php` | GOV-01 to GOV-04, HOME-03 | Four roles, no role grants diaspora submissions, per-person grant, department scope, mission activities limited to Ministry Administrators |
| `tests/unit/AuditLogTest.php` | SEC-04 | Records events, strips secrets, newest first |
| `tests/unit/DemoSeederTest.php` | DEMO-01 | Refuses production, labels every record, no duplicates |
| `tests/feature/HealthTest.php` | SEC-06 | Health check response |

## Not covered yet

- The Content Security Policy header itself. It is added when a response is sent,
  which feature tests skip. The tests check that it is switched on, and it was
  checked by hand with `curl` against `php spark serve`.
- Browser checks: keyboard navigation, screen reader, sticky header on scroll,
  mobile menu, and WCAG 2.2 AA scans. These are planned with Gate 3, when real
  pages exist.
- Completing an emailed sign-in code end to end, which needs a mail server.
