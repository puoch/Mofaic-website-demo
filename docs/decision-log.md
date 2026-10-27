# Decision log

The source of truth for what is confirmed, proposed and deferred. The baseline is
the Requirements & Architecture Brief v2.3. Item IDs (PRI-, NAV-, MIS-, ...) match
the Gate 0 traceability matrix.

**Confirmed** = chosen by the project sponsor. **Proposed** = suggested, not yet
approved. **Deferred** = intentionally left open. Proposed and deferred items must
not be treated as Ministry policy.

## Confirmed after the brief

| Date | Decision | Source |
| --- | --- | --- |
| 2026-10-26 | English is the only launch language; right-to-left support stays in the design | Sponsor, Gate 0 Q2 |
| 2026-10-26 | Development and staging on a VPS; live site in MOFAIC's on-premises data centre | Sponsor, Gate 0 Q4 |
| 2026-10-26 | One modular CodeIgniter 4 application | Sponsor, Gate 0 Q1 |
| 2026-10-26 | Super Administrators get no diaspora submission access by role; access is granted per person (PD-02) | Sponsor, Gate 0 Q3 |
| 2026-10-26 | Accessibility target is WCAG 2.2 level AA (PD-14) | Sponsor, Gate 0 Q5 |
| 2026-10-26 | Coding approved ("APPROVE GATE 2: START CODING"). Gate 1 was not reviewed separately | Sponsor |
| 2026-10-27 | Gate 3 approved ("APPROVE GATE 3: START CODING") | Sponsor |
| 2026-10-27 | Gate 4 approved ("APPROVE GATE 4: START CODING"). The G3-01 to G3-05 proposals are still unreviewed | Sponsor |
| 2026-10-27 | Gate 5 started on the sponsor's "proceed" after the Gate 4 pull request. This does not approve any G3 or G4 proposal | Sponsor |

## Proposed, in use as configurable defaults

| ID | Proposal | Where |
| --- | --- | --- |
| PD-01 | CodeIgniter Shield; emailed code at every staff sign-in; no public registration; 12-character passwords | `app/Config/Auth.php` |
| PD-04 | ARCHIVES linked from the footer until the Ministry decides | `Config\Navigation::$archivesPlacement` |
| PD-16 | Demo data labelled `DEMO, NOT OFFICIAL`, using `example.invalid`, blocked in production | `DemoSeeder` |
| PD-17 | Front-end assets served locally, no CDN | `public/assets/vendor/` |
| PD-12 | Media limits: images JPG/PNG/WebP up to 10 MB, PDF up to 20 MB, MP3 up to 50 MB, MP4 up to 200 MB; audio and video may instead link to an `https://` address | `Config\Media` |
| PD-13 | Every saved version of content is kept; nothing is pruned until retention is decided | `content_revisions` |
| G3-01 | Search on News & Press uses a plain text match on title, summary and body, not MySQL full-text (PD-15 stays unused) | `Site\News::list` |
| G3-02 | The country finder says neutrally that no mission is recorded for a country, with no referral, because the no-match rule is undecided (deferred item 5) | `site/missions/country.php` |
| G3-03 | External audio and video, and social media accounts, are shown as links, not embedded players or feeds. Embedding would load content from other hosts and needs a Content Security Policy change and Ministry approval | `site/partials/media.php`, `Site\News::social` |
| G3-04 | Department Editors and Read-Only Staff can open the publishing screens; site-wide lists (tags, quick links, social accounts, organogram, emergency contacts, contact information) are edited by Ministry Administrators only | `Libraries\ContentPolicy` |
| G3-05 | System pages from the brief's structure can be filled in and published but not deleted, moved or renamed | `StructureSeeder`, `lockedForSystem` |
| PD-05 | Inbox statuses: New, In progress, Awaiting information, Closed | `Config\Submissions::$statuses` |
| PD-06 | Notification emails carry only the submission type, its reference and a staff-area link | `SubmissionService::notify()` |
| PD-07 | When no mission serves the country of residence: say so, show the headquarters contact text (Ministry-supplied block, hidden while empty) and keep the registration | `site/diaspora/missions.php` |
| PD-08 | Diaspora registration: name required, nature of inquiry optional | `Config\Submissions::$registrationNameRequired` |
| PD-09 | Newsletter double opt-in by emailed link (valid 7 days), signed unsubscribe link in every issue, subscriptions stored apart from registrations | `Libraries\Newsletter` |
| PD-10 | Email is queued and sent by `php spark mail:send` from cron through the SMTP relay in `.env` | `Libraries\MailQueue` |
| PD-11 | Spam protection without outside services: hidden field, signed form time (3 seconds to 2 hours), 10 submissions per form per address per hour, CSRF | `Libraries\FormGuard`, `Config\Submissions` |
| G4-01 | Public forms stay closed until the privacy notice block holds approved text; registration also needs the voluntary-registration statement. There is no consent checkbox because consent wording is undecided | `Config\Submissions::$requiredBlocks` |
| G4-02 | Inquiry fields: name, email and message required; telephone and subject optional. No acknowledgement email; the visitor sees a reference on screen | `Config\Submissions::$inquiryFields` |
| G4-03 | Diaspora access is granted per person by a Super Administrator, only to Ministry Administrators or members of a diaspora department, never to oneself. View and handle are separate levels | `Libraries\DiasporaAccess` |
| G4-04 | Diaspora announcements, events and newsletters are articles in diaspora categories and must belong to a diaspora department. Ministry Administrators can also publish them (they can publish in any department) | `ContentPolicy::isDiasporaContent()` |
| G4-05 | A newsletter issue is a published "Diaspora newsletter" article, sent once, as plain text with the summary and a link to the website | `Libraries\Newsletter::sendIssue()` |
| G4-06 | Staff cannot delete submissions or subscriptions, and none are deleted automatically, until retention and deletion rules exist (deferred items 6 and 13) | — |
| G4-07 | Personal data is protected by access control, TLS and audit. It is not encrypted column by column; at-rest encryption is part of deferred item 10 | — |
| G2-01 | Permission matrix in `docs/permissions.md` | `Config\AuthGroups` |
| G5-01 | `php spark golive:check` blocks the live launch while any proposal in this table or any deferred item below is not recorded as settled in `Config\GoLive` | `Libraries\GoLiveCheck` |
| G5-02 | Browser and accessibility checks (axe-core, Playwright) are a development tool run before each release, not installed on the live server | `tests/browser/` |
| G5-03 | Backups: nightly at 01:30, kept 14 days on the server and copied off it; monthly restore drill straight after a backup, with the evidence kept. Real targets are deferred item 9 | `scripts/`, `docs/deployment.md` |
| G5-04 | Quarterly access review and same-day offboarding (ban, never delete), at least two Super Administrators, using `php spark staff` until there is a staff-account screen | `docs/operations.md`, `Commands\Staff` |
| G5-05 | Links use the site accent colour (7.2:1 on the footer band) instead of Bootstrap's blue (4.1:1, fails AA). Brand colours are deferred item 12 | `public/assets/css/site.css` |
| G2-02 | Server baseline: Ubuntu 24.04 LTS, Nginx, PHP-FPM 8.3, MySQL 8.4 | `docs/deployment.md` |

## Proposed, not used yet

PD-03 (missing-translation notice), PD-15 (MySQL full-text search).

## Deferred (brief section 6)

1. Translation workflow and missing-translation behaviour
2. Archives classification, structure and navigation placement
3. Leadership details, history, diplomatic code, Heads of Mission list, organogram
4. Media types, limits, retention and storage
5. Mission coverage rules, no-match handling, authoritative mission data
6. Diaspora field requiredness, consent wording, privacy notice, retention, deletion
7. Newsletter transport, confirmation, unsubscribe
8. Public inquiry fields, timeframes, roles, acknowledgement
9. Performance baseline, 10x projections, uptime, backup and recovery targets
10. On-premises hardware, topology, network boundaries, encryption, security assessment
11. Launch team capacity, training, system administration owner, upgrade policy
12. Brand guidelines, official assets, domain, launch date
13. Retention periods for submissions, audit logs and revisions
14. Shared media library
15. Software licence for this codebase (not in the brief; the starter's MIT licence file was removed pending a Ministry decision)
