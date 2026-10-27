# Architecture

> **Review note.** Coding began straight after Gate 0, so this architecture
> summary has not had a separate Gate 1 sign-off. Treat the parts marked
> *proposed* as open until the sponsor approves them.

## Shape

One CodeIgniter 4 application owned by the Ministry, organised into modules
(approved 26 October 2026). Gate 2 provides the shared foundation that every
module uses.

```
Browser ──HTTPS──▶ Web server (Nginx) ──▶ PHP-FPM 8.3 ──▶ CodeIgniter 4 app ──▶ MySQL 8.4
                                                             │
                                                             └──▶ SMTP relay (staff codes, later notifications)
```

There are no other outbound connections and no integration with government
systems (brief section 1).

## Foundation (Gate 2)

| Part | Where | Purpose |
| --- | --- | --- |
| Public layout | `app/Views/layouts/public.php`, `partials/primary_nav.php` | Same fixed navigation on every public page |
| Navigation config | `app/Config/Navigation.php` | Approved order, missions hierarchy, ARCHIVES placement |
| Languages | `app/Config/Languages.php`, `app/Language/en/` | Language-prefixed URLs (`/en/...`), text direction |
| Staff sign-in | CodeIgniter Shield | Session sign-in, emailed second step, no public registration |
| Roles and permissions | `app/Config/AuthGroups.php` | The four roles; see [permissions.md](permissions.md) |
| Department scope | `app/Libraries/DepartmentAccess.php` | Editors publish only in their departments |
| Audit log | `app/Libraries/AuditLog.php`, `audit_events` table | Records staff sign-in, and from Gate 3 every content and media change. It is also the publishing log |
| Health check | `GET /health` | For monitoring; returns status only |
| Demo data | `app/Database/Seeds/DemoSeeder.php` | Labelled fictional data, blocked in production |

## Content modules (Gate 3)

| Part | Where | Purpose |
| --- | --- | --- |
| Content types | `app/Config/ContentTypes.php` | One definition per type drives the admin list, form, validation and scope |
| Content service | `app/Libraries/ContentService.php` | Save, publish, unpublish, delete, versions and restore; writes the audit log |
| Content policy | `app/Libraries/ContentPolicy.php` | Who may change what; see [permissions.md](permissions.md) |
| Media | `app/Libraries/MediaService.php`, `app/Config/Media.php` | Checked uploads stored under random names in `public/uploads/`, and external audio/video links |
| Missions directory | `app/Libraries/MissionDirectory.php` | Published missions, coverage, related missions, country finder |
| Public controllers | `app/Controllers/Site/` | Home, About and Services pages, News & Press, Archives, Missions, Contacts (text only; the inquiry form is Gate 4) |
| Editing screens | `app/Controllers/Admin/Content.php`, `Admin/Publishing.php` | Generic editing for every type; publishing dashboard and publishing log |
| Page structure | `app/Database/Seeds/StructureSeeder.php` | The brief's page tree as empty system drafts |

The brief's modules are kept apart by namespace and configuration within
`app/` (Site controllers, one content-type definition each, one library per
concern) rather than separate `app/Modules/<Name>/` folders as first proposed.
A shared generic editor was simpler than seven near-identical module folders.
This is a proposal for review.

### Content tables

| Table | Contents |
| --- | --- |
| `pages` | About MFA&IC, About South Sudan and Services pages, in a tree (`parent_id`, `path`) |
| `articles`, `tags`, `article_tag` | News, statements and mission activities |
| `galleries`, `archive_items` | Photo albums and archive entries (no classification until the Ministry decides) |
| `missions`, `mission_coverage`, `countries` | Missions abroad and foreign missions, which countries each serves (resident or non-resident) |
| `media`, `media_links` | Files and external links, attached to any record |
| `content_revisions` | A full copy of each saved version |
| `quick_links`, `social_accounts`, `site_blocks`, `org_units` | Home page links, official accounts, fixed text blocks (emergency contacts, contact information, privacy notice and others), organogram |

## Diaspora and inquiries (Gate 4)

| Part | Where | Purpose |
| --- | --- | --- |
| Forms | `app/Controllers/Site/Contacts.php`, `Site/Diaspora.php`, `Site/FormController.php` | Inquiry, registration with mission choice, contribution, newsletter subscription |
| Spam protection | `app/Libraries/FormGuard.php` | Hidden field, signed form time, hourly limit per address (PD-11) |
| Submissions | `app/Libraries/SubmissionService.php`, `app/Config/Submissions.php` | Storing, references, notifications, status, assignment, notes |
| Inboxes | `app/Controllers/Admin/Inbox.php` | Public Inquiries Inbox and Diaspora inbox |
| Diaspora access | `app/Libraries/DiasporaAccess.php`, `Admin/DiasporaAccess.php` | Per-person grants (PD-02) |
| Newsletter | `app/Libraries/Newsletter.php`, `Admin/Newsletter.php` | Double opt-in, unsubscribe, sending an issue |
| Mail | `app/Libraries/MailQueue.php`, `app/Commands/SendMail.php` | Queued email sent by `php spark mail:send` (PD-10) |

### Gate 4 tables

| Table | Contents |
| --- | --- |
| `inquiries` | Public inquiries |
| `diaspora_registrations`, `diaspora_contributions` | Diaspora submissions (no identity, financial or file fields) |
| `submission_notes` | Staff-only notes on any submission |
| `newsletter_subscriptions`, `newsletter_sends` | Subscriptions (kept apart from registrations) and which issues were sent |
| `mail_queue` | Outgoing email |
| `notification_rules` | Who is emailed for each submission type, and the subject and opening text |
| `diaspora_country_pages` | Diaspora by Country, one page per country of residence |

The registration flow keeps only the new registration's id in the visitor's
session, so the mission-choice and confirmation pages cannot be opened for
someone else's registration.

## Modules planned for later gates (proposed)

| Gate | Module |
| --- | --- |
| 5 | Production readiness: accessibility checks, load baseline, backups and restore drill, monitoring, handover |


## Data in the foundation

| Table | Contents |
| --- | --- |
| `users`, `auth_*` | Shield staff accounts, identities, groups, per-person permissions, sign-in history |
| `settings` | CodeIgniter Settings store (used by Shield) |
| `departments` | Ministry departments. Empty until the Ministry supplies the list |
| `department_user` | Which editors belong to which departments (many to many) |
| `audit_events` | Append-only record of staff activity |

## Security baseline

- CSRF tokens on every form; secure headers and a Content Security Policy on every response.
- No inline scripts. All assets are served from the site itself.
- Passwords are at least 12 characters and checked against Shield's password rules.
- `/admin` needs a signed-in staff account with `admin.access`. Each later
  module adds its own permission check.
- The audit log never stores passwords or tokens.
- Non-production pages show a "not an official website" banner and ask search engines not to index them.
