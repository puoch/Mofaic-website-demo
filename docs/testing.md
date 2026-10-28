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
| `tests/unit/PermissionsTest.php` | GOV-01 to GOV-04, HOME-03 | Four roles, only Super Administrator grants diaspora submissions by role, per-person grant, department scope, mission activities limited to Ministry Administrators |
| `tests/unit/AuditLogTest.php` | SEC-04 | Records events, strips secrets, newest first |
| `tests/unit/DemoSeederTest.php` | DEMO-01 | Refuses production, labels every record (including demo countries, missions and articles), no duplicates |
| `tests/feature/HealthTest.php` | SEC-06 | Health check response |
| `tests/unit/ContentServiceTest.php` | GOV-05, GOV-06, NEWS-06, ABT-04 | Slugs, page paths and loops, tags, publish dates, revisions and restore, protected system records, audit events |
| `tests/unit/ContentPolicyTest.php` | GOV-02, GOV-03, HOME-03, DIA-11 | Department scope, mission activities, site-wide and missions scopes, restore permission |
| `tests/unit/MediaServiceTest.php` | NEWS-02, NEWS-03, ARC-01 | Uploads checked by detected type, random file names, external links https only, removal deletes the file |
| `tests/feature/PublicContentTest.php` | HOME-01 to HOME-05, ABT-01 to ABT-04, ASS-01, SRV-01 to SRV-06, NEWS-01 to NEWS-06, ARC-01, ARC-03, CON-01 | Only published content appears, drafts and unpublished parents return 404, search and date filters, tags, galleries, Markdown escaping, empty-state messages, emergency and contact text hidden until entered |
| `tests/feature/MissionsTest.php` | MIS-01 to MIS-06, HOME-06 | Directory by category, profiles, coverage, related missions, country finder, neutral no-match message |
| `tests/feature/AdminContentTest.php` | GOV-02, GOV-03, GOV-05, GOV-06, HOME-03 | Editors publish in their departments without approval, cannot reach others, mission activities, restore, coverage, 403 screens |
| `tests/unit/FormGuardTest.php` | SEC-02 | Hidden field, too fast, expired, tampered or borrowed tokens, hourly limit |
| `tests/unit/SubmissionServiceTest.php` | CON-03, CON-05, GOV-07, SEC-04 | References, notifications per type with no personal details, audit without personal details, status, assignment, notes |
| `tests/unit/NewsletterTest.php` | DIA-09, DIA-10 | Double opt-in, link expiry, no flooding, signed unsubscribe, issue sent once to confirmed subscribers only |
| `tests/unit/MailQueueTest.php` | CON-03 | Queue order, retries, failed messages |
| `tests/unit/DiasporaAccessTest.php` | GOV-04 | No role has access, eligibility, grants audited, no self-grant |
| `tests/feature/ContactFormTest.php` | CON-01 to CON-04, EXC-01 | Form closed without privacy notice, valid inquiry and reference, validation, spam checks, no uploads, separate inbox |
| `tests/feature/DiasporaFormsTest.php` | DIA-01 to DIA-12, EXC-02 | Voluntary statement, no identity/financial/file fields, required fields, all serving missions listed and none chosen, only listed missions accepted, nothing sent to missions, no-match, separate newsletter opt-in, anonymous contributions, Stay Informed, Diaspora by Country |
| `tests/feature/InboxTest.php` | CON-04, CON-05, DIA-13, GOV-04, SEC-04 | No role opens the Diaspora inbox, per-person grants, audited views, view-only cannot change, status, assignment, notes, inbox separation, access screen |
| `tests/feature/DiasporaPublishingTest.php` | DIA-09, DIA-11, DIA-12 | Diaspora editors publish directly, diaspora department required, no mission activities, one page per country, newsletter sending, notification rules |
| `tests/unit/LocalAssetsTest.php` | PRI-09, PD-17 | Every stylesheet and script the layouts load is in the repository, Bootstrap matches its checksums, no view hard-codes another host, local mode loads only local files, CDN mode carries integrity hashes that match the vendored files and the fallback script, and the Content Security Policy allows the CDN only in CDN mode |
| `tests/unit/HostingTest.php` | Hosting without cron | Mail waits for cron by default; with `hosting.sendMailInRequest` single messages go out when queued and staff pages send a few waiting newsletter messages; the upload cap lowers every kind |
| `tests/feature/MaintenanceTest.php` | Hosting without SSH | `/maintenance` is 404 without a long token, refuses a wrong token, runs migrations, creates staff accounts with valid roles and strong passwords only, limits attempts, audits every use |
| `tests/unit/ApacheRulesTest.php` | Shared hosting | The root `.htaccess` sends every request into `public/` and fails rather than serving files without `mod_rewrite`; `public/uploads/.htaccess` refuses PHP files |
| `tests/unit/GoLiveCheckTest.php` | G5-01 | Reads every proposal and deferred item from the decision log, unsettled ones block, a decision passes only with its recorded source, shipped approvals match the log, served project files fail, demo data, missing Ministry text, backup age, Super Administrator count |
| `tests/unit/ContentSafetyTest.php` | DEMO-01 | No placeholder text copied from other governments' sites (terms listed in the test), no Cyrillic letters (or the Latin letters they are often transliterated with) anywhere in the repository |
| `tests/feature/StaffScreenTest.php` | GOV-01, SEC-04, G5-04 | Only Super Administrators open the staff screen; accounts created with a role and strong password, roles changed, bans with reason, department membership, all audited; nobody changes their own role or bans themselves; the last Super Administrator stays |
| `tests/unit/FullTextQueryTest.php` | NEWS-04, PD-15 | Search words become a full-text query: every word required, prefix match, visitor operators removed, short words and stopwords left to the plain match |
| `tests/unit/StaffCommandTest.php` | GOV-01, SEC-04 | Access review list, ban and unban, department membership, all audited; unknown accounts change nothing |

## Browser checks

`tests/browser/check.mjs` (Node.js, Playwright, axe-core) checks pages in a
real browser: WCAG 2.2 AA rules, outside requests, fixed header, skip link,
mobile menu and reflow. It needs a running copy of the site, so CI does not
run it. See [accessibility.md](accessibility.md) for how to run it and the
Gate 5 results.

`tests/browser/cdn-fallback.mjs` checks the CDN mode (PD-17) against a copy
started with `assets.bootstrapSource = cdn`: the CDN works, the CDN returns a
changed file, and the CDN cannot be reached. In the last two the local copy
must load. Run `check.mjs` in `local` mode, because it treats every request to
another host as a failure. Results: [evidence/gate-5/cdn-fallback.md](evidence/gate-5/cdn-fallback.md).

## Backup scripts

`scripts/restore-drill.sh` is the test for `scripts/backup.sh` and
`scripts/restore.sh`. It needs MySQL and was run by hand at Gate 5; the
evidence is in [evidence/gate-5/restore-drill.md](evidence/gate-5/restore-drill.md).

## Not covered yet

- The Content Security Policy header itself. It is added when a response is sent,
  which feature tests skip. The tests check that it is switched on, and it was
  checked by hand with `curl` against `php spark serve`. At Gate 5 the browser
  check also saw it enforced: Chromium refused an injected inline script until
  the check was told to bypass the policy.
- Screen readers, zoom, Firefox and Safari, and content written by staff. The
  automated browser checks ran in Chromium only (see accessibility.md).
- The browser checks and the restore drill in CI. Both ran by hand at Gate 5.
- Real email delivery. Tests stop at the mail queue; `mail:send` has not been
  run against an SMTP relay.
- An upload through the real editing form. The media tests replace PHP's upload
  check with a test double, because `is_uploaded_file()` only accepts real HTTP
  uploads.
- Completing an emailed sign-in code end to end, which needs a mail server.
