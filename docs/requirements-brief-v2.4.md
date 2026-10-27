# MOFAIC WEB — Portable Requirements & Architecture Brief
Version: 2.4 portable baseline | Prepared: 27 October 2026 | Replaces: v2.3 of 26 October 2026 | Status: build delivered to Gate 5 on a development basis; live launch blocked until the Ministry settles the items in section 6

## Purpose and reading rules
This file is the portable source of truth for AI coding assistants and developers working on MOFAIC WEB, the public website and internal content-management application for the Ministry of Foreign Affairs & International Cooperation of South Sudan. Version 2.4 is v2.3 with the sponsor's decisions of 26 and 27 October 2026 written in. The detailed record of each decision, with its date and source, is `docs/decision-log.md` in the repository github.com/puoch/Mofaic-website-demo; where this brief and the decision log differ, the decision log wins and this brief should be corrected.

**Confirmed** means specifically chosen by the project sponsor. **Confirmed (interim)** means the sponsor approved it as a working default only until the Ministry decides the deferred item named beside it. **Proposed** means suggested but not yet approved. **Deferred** means intentionally postponed. An AI must not silently convert proposed or deferred items into requirements. Proposal IDs (PD-, G2- to G5-) match the decision log; requirement IDs (PRI-, NAV-, MIS- and so on) match the Gate 0 traceability matrix.

Sponsor approval is not Ministry approval. Several confirmed items (hosting for staging and testing, outside services such as an SMTP provider or a CDN) may still need formal MOFAIC sign-off before launch.

## What changed since v2.3
- Section 1: launch language, architecture, authentication, hosting phases, accessibility target, server baseline and asset delivery are now confirmed.
- Section 2: interim rules for ARCHIVES placement, no-match handling, registration fields, inquiry fields, newsletter opt-in, social media display and search are now confirmed.
- Section 3: per-person diaspora access, inbox statuses, notification content and the staff accounts screen are confirmed.
- Section 4: spam protection, encryption approach, backups, access review and the go-live check are confirmed; retention remains deferred.
- Section 6: item 1 is narrowed (launch language decided) and item 15 (software licence) is added.
- New section 10: delivery status.

## 1. Non-negotiable project principles
- Stack: PHP 8.3 + CodeIgniter 4.7; Bootstrap 5.3, HTML/CSS and limited JavaScript for the public interface; MySQL 8.4 as the primary relational database. **Confirmed.**
- Authentication: CodeIgniter Shield 1.4, with an emailed code at every staff sign-in, no public registration and passwords of at least 12 characters (PD-01). **Confirmed.**
- Architecture: one Ministry-owned CodeIgniter 4 application, logically separated into modules. **Confirmed 26 October 2026.**
- Hosting: development and staging on a VPS; the live site in the **MOFAIC on-premises data centre**. **Confirmed 26 October 2026.** An early test and demonstration copy runs on InfinityFree's free shared hosting, deployed from GitHub, with fictional data only. **Confirmed 27 October 2026.** This does not change the live hosting decision. Keep the application portable between these environments. Hardware topology, network edge, mail delivery method and disaster recovery for the live site remain deferred (section 6, items 7, 9 and 10).
- Proposed server baseline: Ubuntu Server 24.04 LTS, Nginx, PHP-FPM 8.3, MySQL 8.4 (G2-02). **Confirmed** as the baseline to plan with; the Ministry's own server standards may override it (item 10).
- Front-end assets: Bootstrap is served from the Ministry's own server by default. A per-server switch can serve it from a CDN with Subresource Integrity and an automatic local fallback, for the VPS phase (PD-17, changed by the sponsor). **Confirmed.**
- MOFAIC technical staff will maintain and operate the system; maintainability, knowledge transfer and operational documentation are essential.
- Capacity planning target: plan for **10x growth in users and services over 3–5 years**. Baseline volumes are still unknown; do not claim a specific concurrency, infrastructure size or performance guarantee before measurement (item 9).
- Languages: **English is the only launch language. Confirmed 26 October 2026.** The design keeps internationalization, language-prefixed addresses and right-to-left support so further languages can be added later. Translation workflow remains deferred (item 1).
- Accessibility: **WCAG 2.2 level AA** (PD-14). **Confirmed 26 October 2026.**
- No integration with other government information systems. Outbound official links, social media links and a necessary email service are distinct from such integration.
- A visibly consistent global navigation on every public page, fixed at the top while scrolling.

## 2. Information architecture (main navigation)
HOME | MISSIONS | ABOUT MFA&IC | ABOUT SOUTH SUDAN | SERVICES | NEWS / PRESS | SSD DIASPORA | CONTACTS. ARCHIVES exists as a section; until the Ministry decides its placement it is linked from the footer, and moving it into the top bar is a configuration change (PD-04). **Confirmed (interim, item 2).**

### HOME — confirmed
Distinct homepage areas: featured news, Minister's recent engagements, activities of South Sudanese diplomatic missions, quick links, official emergency contacts, and a **country-based Diplomatic Mission Finder**. Minister's engagements appear through news articles tagged "Minister's engagements". Mission activities are published **only by central Ministry administrators**. The emergency contacts area stays hidden until the Ministry supplies approved text (see section 9). Reference for content organization (not copying assets/layout): https://mfa.gov.rs/en .

### MISSIONS — confirmed hierarchy, do not restructure
1. Missions Abroad
   - Embassies
   - Consulates
   - Permanent Missions
   - Non-resident Missions
2. Foreign Missions in South Sudan
   - Embassies
   - International Agencies
Each mission has an individual public profile with mission name/type, physical location, contacts, head of mission and activities. A profile also displays the countries it serves and other diplomatic representations serving the same country. Country-based directories and mission finding are required. Support relationships in which a non-resident mission serves a country where it is not physically located. Suggested fields such as website, insignia and embassy service particulars are not automatically mandatory. When no mission is recorded for a country, the country finder says so neutrally, with no referral (G3-02). **Confirmed (interim, item 5).** Reference: https://mfa.gov.rs/en/diplomatic-missions/serbian-diplomatic-missions .

### ABOUT MFA&IC
Confirmed: a **single Leadership page** for Minister, Deputy Minister and Undersecretary; an **interactive organogram and downloadable organogram document**; bilateral and multilateral relations as informational pages. Ministry history, diplomatic code and Heads of Mission list exist as pages, but their content remains open (item 3). Biographies, photographs and chart structure must come from verified Ministry-provided content.

### ABOUT SOUTH SUDAN — confirmed four categories
Adapt the *structure* of https://www.srbija.gov.rs/tekst/en/5/about-serbia.php : (1) Basic Information, (2) National Symbols, (3) People, Languages and Religions, (4) Ethnic and Cultural Diversity. Do not copy Serbian facts, branding, images or copyrighted text. South Sudan country information must be verified and supplied or approved by Ministry editors. Research drafts may be prepared for the Ministry's review but are not published content until approved.

### SERVICES — confirmed
Two main expandable categories: Citizens and Foreigners. Citizens: Traveling Abroad (Visa Information, Travel Advice/Notices), Consular Services, Scholarships, Document Authentication. Foreigners: Visa Requirements and Entry Requirements. Consular services supply information, downloadable forms and links to official external portals, **not direct online consular applications**. Scholarships: announcements, downloadable documents, external application links. Document Authentication: requirements, procedures and office contacts, not uploads. Visa/entry: **separate Citizen and Foreigner informational pages** and links to responsible agencies. New service categories may be added later. Pages from this structure can be filled in and published but not deleted, moved or renamed (G3-05). **Confirmed.**

### NEWS / PRESS — confirmed
Four expandable categories: News, Statements, Social Media, Gallery. Statements are articles with downloadable PDFs, images, audio and video attachments. Gallery supports photo, video and audio media. News and statements have chronological listings with keyword search and date filters. Keyword search uses MySQL full-text search on title, summary and text: every word must appear and word beginnings match (PD-15). **Confirmed 27 October 2026; G3-01, a plain text match, is withdrawn.** News tagging powers Minister's engagements. Mission activities are centrally published.
- Social Media shows official accounts as links, and external audio and video as links, not embedded feeds or players, because embedding loads content from other hosts (G3-03). **Confirmed.** Embedding can be revisited with Ministry approval.

### ARCHIVES — partially confirmed
Support text and uploaded or externally linked image, video and audio media, with optional links to a related news item and gallery album. Archive classification and placement in navigation remain deferred (item 2). No automatic archival or retention policies.

### CONTACTS — confirmed
Publish official contact information; accept public inquiries. Submission sends configurable administrative email notifications and appears in a **Public Inquiries Inbox**, separate from the diaspora inbox, with statuses, assignment and internal notes.
- Inquiry fields: name, email and message required; telephone and subject optional. No acknowledgement email; the visitor sees a reference on screen (G4-02). **Confirmed.** Handling timeframes and retention remain deferred (items 8 and 13).

### SSD DIASPORA — confirmed
Four information areas: Register; Contribute; Stay Informed; Diaspora by Country. Registration is **voluntary** and explicitly described as such. Fields: name (required, PD-08), full address (optional), city (required), locality (optional), country of residence (required), email and phone (at least one required), nature of inquiry (optional, PD-08) and requested consular service help (optional). **Do not request passport, national ID, financial/banking or document uploads.** The form warns visitors not to enter such numbers.
- After registration, **show all missions serving the person's country of residence and let the visitor choose**, then a confirmation page with the chosen mission's contact details. Do **not** auto-pick one mission; do not transfer registration details to a mission. When no mission serves the country: say so, show the headquarters contact text supplied by the Ministry, and keep the registration (PD-07). **Confirmed (interim, item 5).**
- Contribute: **one general contribution form with category selection** (ideas/recommendations, feedback, development initiatives/proposals, donation/investment information); anonymous contributions permitted; email and phone optional. No payment or financial processing.
- Stay Informed: diaspora announcements, events and newsletters on the website and by email. Newsletter enrolment is a **separate voluntary opt-in** with its own checkbox, confirmed by an emailed link valid for 7 days, with a signed unsubscribe link in every issue; subscriptions are stored apart from registrations (PD-09). A newsletter issue is a published "Diaspora newsletter" article, sent once as plain text with its summary and a link (G4-05). **Confirmed.** Diaspora content is articles in diaspora categories belonging to a diaspora department; diaspora department editors and Ministry Administrators publish it directly (G4-04).
- Diaspora country information organised by residence country; content to be supplied.
- One administrative diaspora inbox with filters by registration/contribution type, **separate from the public inquiries inbox**.
- Public forms stay closed until the Ministry's approved privacy notice is entered; registration also needs the voluntary-registration statement. There is no consent checkbox until consent wording is decided (G4-01). **Confirmed (interim, item 6).**

## 3. Staff access, governance and audit — confirmed
Four roles: Super Administrator; Ministry Administrator; Department Editor; Read-Only Staff. Each account has one role. Department Editors may be assigned to **multiple departments**. Authorized editors publish directly in permitted scopes, with no universal second approval. Mission activities are published only by central Ministry administrators. The permission matrix is `docs/permissions.md` (G2-01). Site-wide lists (tags, quick links, social accounts, organogram, emergency contacts, contact information) are edited by Ministry Administrators only (G3-04).
- **Diaspora submissions:** no role, including Super Administrator, gives access. A Super Administrator grants access per person, only to Ministry Administrators or members of a diaspora department, never to themselves, at "view" or "view and handle" level (PD-02, G4-03). **Confirmed.**
- Inbox statuses: New, In progress, Awaiting information, Closed (PD-05). Assignment and staff-only notes. **Confirmed.**
- Notifications: administrators set rules per submission type (recipients, subject, opening text). Emails carry only the submission type, its reference and a staff-area link, never the visitor's details (PD-06). **Confirmed.**
- Revision history and publishing logs. Every saved version is kept until the Ministry sets a retention period (PD-13, interim, item 13). Authorized administrators can restore a retained version; restoration is logged.
- Staff accounts: Super Administrators manage accounts on a staff accounts screen in the administration area (also available as a server command): access-review list, new accounts, role changes, banning with a reason, and department membership. Nobody can change their own role or ban themselves, and the last active Super Administrator cannot be removed. Accounts are banned, never deleted. **Confirmed 27 October 2026.**
- Access review and offboarding: quarterly access review, same-day offboarding by banning, at least two active Super Administrators (G5-04). **Confirmed.**

## 4. Security/privacy and operations constraints
MOFAIC WEB collects ordinary personal data (names, residential locality/address, contact information, inquiries); describing it as "non-sensitive" does NOT remove privacy/security obligations. Require TLS, protected administrative access, least-privilege permissions, safe form handling, spam prevention and protection against common web risks. Audit administration activity and protect private files. Public media and private submission data are not treated identically.
- Spam protection without outside services: hidden field, signed form time (3 seconds to 2 hours), 10 submissions per form per address per hour, CSRF protection (PD-11). **Confirmed.**
- Personal data is protected by access control, TLS and audit, not column-by-column encryption; at-rest encryption is part of item 10 (G4-07). **Confirmed (interim).**
- Staff cannot delete submissions or subscriptions, and nothing is deleted automatically, until retention and deletion rules exist (G4-06). **Confirmed (interim, items 6 and 13).**
- Media limits: images JPG/PNG/WebP up to 10 MB, PDF up to 20 MB, MP3 up to 50 MB, MP4 up to 200 MB; audio and video may instead link to an https address (PD-12). **Confirmed (interim, item 4).**
- Email is queued and sent through an SMTP relay set per server; on hosts without scheduled jobs it can be sent as each message is queued (PD-10). **Confirmed.**
- Demonstration data is labelled "DEMO, NOT OFFICIAL", uses reserved example addresses and is blocked in production (PD-16). **Confirmed.**
- Backups: nightly, kept 14 days on the server and copied off it; monthly restore drill with evidence kept (G5-03). **Confirmed (interim, item 9).**
- A go-live check blocks the live launch while any proposal or deferred item is not recorded as settled by MOFAIC (G5-01). **Confirmed.** Browser and accessibility checks are a development tool run before each release, not installed on the live server (G5-02).
- Links use an accessible accent colour rather than Bootstrap's default blue; brand colours remain deferred (G5-05, item 12).
- MOFAIC owns operations: deployment, backup verification, recovery testing, monitoring, patching, error handling, content maintenance and staff training are documented in the repository (`docs/deployment.md`, `docs/operations.md`), and must be accepted by the Ministry before launch.
- Design for 10x growth in 3–5 years; measure base and peak usage, then load test and set upgrade triggers (item 9).

## 5. Explicit exclusions from current scope
No online visa application processing; no online document authentication submissions; no direct consular application processing; no payment gateway, donations processing or investment transactions; no government-database integration; no automatic transfer of diaspora registration data to diplomatic missions; no invented public content or contact numbers; no copying of Serbia's copyrighted site. Advanced future services are not included simply because the architecture anticipates expansion.

## 6. Open/deferred items — do not silently decide
1. Translation process for later languages (human review, editorial responsibility, missing-translation behaviour). Launch language is decided (English). A missing-translation notice (PD-03) is approved but not built, since it only matters once a second language exists.
2. Archives classification, page structure, placement in global navigation and relationship to galleries.
3. Leadership details, ministry history, diplomatic code, Heads of Mission list; official organizational hierarchy and organogram.
4. Final media limits and types, file retention, storage capacity.
5. Mission country coverage rules and no-match handling; authoritative mission and contact data.
6. Consent wording, privacy notice, lawful handling, data retention and deletion procedure for diaspora and inquiry data.
7. Newsletter email transport on premises and sender reputation management.
8. Inquiry handling timeframes, assigned roles and acknowledgement.
9. Performance baseline, 10x projections, concurrency, uptime, backup frequency, recovery times, redundancy and network capacity.
10. On-premises hardware, topology, operating system, network boundaries, encryption at rest and security assessment criteria.
11. Launch team capacity, training effort, system administration owner, third-party package upgrade policy.
12. Design and brand guidelines, official Ministry content and assets (including use of the national coat of arms and flag colours), approved domain and launch date.
13. Formal retention periods for submissions, audit logs and content revisions.
14. Whether and how to build an internal shared media library.
15. Software licence for the codebase (added after v2.3; the starter template's licence file was removed pending a Ministry decision).

## 7. AI developer operating rules
- Read this brief and `docs/decision-log.md` completely before changing anything; show a coverage matrix and list ambiguities before writing code.
- Treat *confirmed* requirements as hard constraints and *proposed/deferred* items as questions or clearly isolated configuration choices. Do not implement invented requirements as Ministry policy.
- Record every new decision in the decision log with its date and source, then issue a new version of this brief.
- Do not claim code works without showing relevant tests/results. Never fabricate government services, official contact information, mission listings or legal text.
- Respect the latest explicit sponsor instructions as controlling, but disclose conflicts with this baseline and request sign-off for scope changes.
- Build incrementally with test evidence and Ministry handover documentation. Avoid unnecessary frontend frameworks, distributed infrastructure or cloud-only dependencies.

## 8. Official reference links (information structure only)
- Serbia MFA homepage: https://mfa.gov.rs/en
- Serbian diplomatic missions: https://mfa.gov.rs/en/diplomatic-missions/serbian-diplomatic-missions
- Government of Serbia About Serbia: https://www.srbija.gov.rs/tekst/en/5/about-serbia.php
- CodeIgniter user guide: https://codeigniter.com/user_guide/
- CodeIgniter Shield: https://github.com/codeigniter4/shield

## 9. Source-content caution
The original HOME.docx included a "South Sudanese in distress" note with **Kenyan** references, a `mfa.go.ke` email address and Kenya-related instructions. Treat that text as unverified placeholder material. Do not publish or replicate it as official South Sudan contact guidance. The emergency contacts area stays empty until MOFAIC-approved emergency information is supplied.

## 10. Delivery status (27 October 2026)
- Gates 0, 2, 3, 4 and 5 are delivered and merged. Gate 1 was not reviewed separately; coding was approved straight after Gate 0. Gate 5 started on the sponsor's "proceed". Final acceptance has not been given.
- The repository, tests and documentation are at github.com/puoch/Mofaic-website-demo. A test copy with fictional data runs on InfinityFree.
- The live launch is blocked until the Ministry settles the items in section 6 and supplies official content, and until the go-live check passes.
