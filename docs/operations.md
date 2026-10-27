# Operations

How to run MOFAIC WEB day to day once it is live. Server setup, releases and
backups are in [deployment.md](deployment.md).

Everything here is a **proposal** (G5-03 and G5-04 in the
[decision log](decision-log.md)). Who owns system administration, the
support hours and the recovery targets are not decided yet (brief section 6,
items 9 and 11). Names, phone numbers and rota details are left as blanks for
MOFAIC to fill in; none have been invented.

## Who does what (to be filled in by MOFAIC)

| Role | Responsibility | Person |
| --- | --- | --- |
| System administration owner | Server, PHP, MySQL, TLS, backups, security updates | _not yet named_ |
| Application owner | Releases, configuration, the go-live check, access reviews | _not yet named_ |
| Super Administrators (at least two) | Staff accounts, roles, diaspora access grants | _not yet named_ |
| Content lead | Ministry text blocks, mission records, publishing standards | _not yet named_ |
| Data protection contact | Privacy notice, retention and deletion decisions, requests from the public | _not yet named_ |

## Routine tasks

| When | Task | How |
| --- | --- | --- |
| Every minute | Send queued email | cron: `php spark mail:send` |
| Daily 01:30 | Backup | cron: `scripts/backup.sh` |
| Daily | Look at the overnight backup log and `/health` | `tail /var/log/mofaic-web-backup.log`, monitoring |
| Weekly | Install operating system security updates | `sudo apt update && sudo apt upgrade`, then check `/health` |
| Weekly | Look for failed or stuck email | `php spark golive:check --only-blockers` (Operations section) |
| Monthly | Restore drill, keep the evidence file | cron: `scripts/backup.sh && scripts/restore-drill.sh` |
| Monthly | Check PHP, CodeIgniter, Shield and Bootstrap for security releases | [versions.md](versions.md); `composer outdated --direct` |
| Quarterly | Access review (below) | Super Administrator with the application owner |
| Before any release | Tests, browser checks, backup | [testing.md](testing.md), [deployment.md](deployment.md) |
| After any change to `.env` | Go-live check | `php spark golive:check` |

## When something goes wrong

| Symptom | First checks |
| --- | --- |
| `/health` returns 503 | `sudo systemctl status mysql`; database settings in `.env`; disk space (`df -h`) |
| Site shows an error page | `writable/logs/log-<date>.log`; the last release (roll back per deployment.md) |
| Staff do not receive sign-in codes | The mail relay; `php spark mail:send` output; Shield sends sign-in codes directly, not through the queue |
| Notifications or newsletter email not arriving | `mail_queue` rows with status `failed` and their `last_error`; cron for `mail:send` |
| Public forms say they are closed | The privacy notice (and, for registration, the voluntary statement) block is empty under Content > Fixed text blocks |
| "Too many forms" for many visitors at once | Visitors may share one address behind a proxy. Check that the web server passes the real visitor address, then `Config\Submissions::$submissionsPerHour` |
| Suspected account misuse | Ban the account on Admin > Staff accounts (or `php spark staff ban <email>`), then read the audit log at `/admin/audit` |
| Data loss or corruption | Follow "Restoring after a failure" in deployment.md. Restore into a new database first |

New accounts and role changes use Shield's command (see [setup.md](setup.md)):
`php spark shield:user create`, `addgroup`, `removegroup`. Those Shield
commands do not write to the audit log, so note role changes with the access
review records. The `staff` commands do write to it.

Every staff action that changes content, access or submissions is in the audit
log (`/admin/audit`, Super Administrators). It records who, what and when, and
never passwords or the contents of submissions.

## Access review (proposal G5-04)

Every quarter a Super Administrator and the application owner go through the
staff list together and record the result (date, who reviewed, changes made).
The list is on **Admin > Staff accounts** (`/admin/staff`, Super
Administrators only): username, email, role, departments, diaspora access,
status and last sign-in. The same list is available on the server:

```bash
php spark staff list
```

1. Every account belongs to someone who still works for the Ministry and still
   needs it.
2. Each person has the lowest role that covers their work (Read-Only Staff,
   Department Editor, Ministry Administrator, Super Administrator).
3. Department Editors are members of the right departments only
   (Admin > Staff accounts, or `php spark staff department <email> <department-slug> [--remove]`).
4. **Admin > Diaspora access**: each grant is still needed, and "handle" is only
   given to people who answer diaspora submissions. Super Administrators have
   no diaspora access unless granted per person (decision PD-02).
5. There are at least two active Super Administrators, so one absence does not
   lock everyone out (`golive:check` warns otherwise).
6. Anyone who has not signed in for 90 days is asked whether they still need
   access.

## Offboarding (proposal G5-04)

When someone leaves or changes job, on or before their last working day:

1. Ban the account on Admin > Staff accounts, with a reason such as "Left the Ministry" (or `php spark staff ban <email> --reason "Left the Ministry"`).
   Shield signs them out on their next request and refuses new sign-ins. Do
   not delete the account: the audit log and content history point at it.
2. Remove their diaspora access grant (**Admin > Diaspora access**) and their
   department memberships (`php spark staff department ... --remove`).
3. Reassign their open inbox items (status New, In progress or Awaiting
   information) to a colleague.
4. If they held a server or database account, remove it, and change any
   shared passwords they knew (database, backup option files, SMTP relay).
5. If they were a Super Administrator, confirm two others remain.
6. Record the date and who did it with the access review notes.

## Capacity plan

No performance baseline, traffic estimate or 10x projection has been agreed
(brief section 6 item 9), so this records what was measured and how to plan,
not targets.

Measured on the Gate 5 test copy (not the live hardware):

| Measure | Result | Notes |
| --- | --- | --- |
| First visit to a public page | 319 KB uncompressed, 4 requests | 312 KB of it is Bootstrap's CSS and JavaScript; with gzip (deployment.md) about 55 KB |
| Further public pages | 87 to 100 KB uncompressed (median 96 KB) | Measured on PHP's built-in server, which sends no caching headers; the Nginx example caches `/assets/` |
| Requests to other hosts | none on any of 42 public pages | All outside traffic was blocked during the check |
| Storage per inquiry | about 1.3 KB (12 MB for 9,300 rows with a 900-character message) | MySQL 8.0.46 table and index size |
| Backup and restore of the demo data set | under 1 second each | Real data will be larger; time the first live backup and drill |

How to plan once MOFAIC sets the targets:

1. Measure a week of real traffic (requests per minute at peak) from the Nginx
   access log after launch, or estimate it from the current site.
2. Load test a staging copy at ten times that peak, using a tool run inside the
   Ministry network, and record response times.
3. Size storage from media first: uploads dominate (up to 200 MB per video
   under PD-12). Database growth from submissions is small.
4. PHP-FPM workers: start with `pm.max_children` of (server RAM minus 2 GB for
   MySQL and the system) divided by about 64 MB, and adjust from measured use.
5. Move MySQL to its own server only when measurements show it competing with
   PHP for memory or disk.

## Handover and training outline

Proposed sessions, each with a hands-on exercise on the staging copy using
demonstration data only:

| Audience | Session | Covers |
| --- | --- | --- |
| Super Administrators | Accounts and access (1.5 h) | Creating staff accounts, roles, departments, diaspora access grants, banning, the audit log, the quarterly review and offboarding |
| Ministry Administrators | Site-wide content (2 h) | Fixed text blocks (privacy notice, contacts), missions and coverage, countries, quick links, tags, mission activities, ARCHIVES |
| Department Editors | Publishing (1.5 h) | Pages, news, statements, galleries, media rules and alt text, revisions and restore, what "published" means |
| Diaspora department editors | SSD DIASPORA (1.5 h) | Announcements, events, newsletter issues and sending, Diaspora by Country pages, the diaspora inbox |
| Inbox handlers | Handling submissions (1 h) | Statuses, assignment, internal notes, what never to put in a note, privacy duties |
| System administration owner | Running the service (3 h) | Server layout, releases and rollback, `.env`, cron, backups, restore drill, `golive:check`, logs, updates, this runbook |

Handover materials: this repository's `docs/` folder, the decision log, the
latest go-live check output, the latest restore drill evidence and the browser
check report. Writing accessible content (headings, link text, alt text,
plain language) should be part of every editor session, because automated
checks cannot judge it.
