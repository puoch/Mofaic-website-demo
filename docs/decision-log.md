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
| — | Permission matrix in `docs/permissions.md` | `Config\AuthGroups` |
| — | Server baseline: Ubuntu 24.04 LTS, Nginx, PHP-FPM 8.3, MySQL 8.4 | `docs/deployment.md` |

## Proposed, not used yet

PD-03 (missing-translation notice), PD-05 (inbox statuses), PD-06 (no personal data
in notification emails), PD-07 (no-match referral), PD-08 (diaspora name required),
PD-09 (newsletter double opt-in), PD-10 (queued email via MOFAIC relay), PD-11
(spam protection without third parties), PD-15 (MySQL full-text search).

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
