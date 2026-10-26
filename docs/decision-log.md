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

## Proposed, in use as configurable defaults

| ID | Proposal | Where |
| --- | --- | --- |
| PD-01 | CodeIgniter Shield; emailed code at every staff sign-in; no public registration; 12-character passwords | `app/Config/Auth.php` |
| PD-04 | ARCHIVES linked from the footer until the Ministry decides | `Config\Navigation::$archivesPlacement` |
| PD-16 | Demo data labelled `DEMO, NOT OFFICIAL`, using `example.invalid`, blocked in production | `DemoSeeder` |
| PD-17 | Front-end assets served locally, no CDN | `public/assets/vendor/` |
| — | Permission matrix in `docs/permissions.md` | `Config\AuthGroups` |
| — | Server baseline: Ubuntu 24.04 LTS, Nginx, PHP-FPM 8.3, MySQL 8.4 | `docs/deployment.md` |

## Proposed, not used yet

PD-03 (missing-translation notice), PD-05 (inbox statuses), PD-06 (no personal data
in notification emails), PD-07 (no-match referral), PD-08 (diaspora name required),
PD-09 (newsletter double opt-in), PD-10 (queued email via MOFAIC relay), PD-11
(spam protection without third parties), PD-12 (media limits), PD-13 (keep all
revisions), PD-15 (MySQL full-text search).

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
