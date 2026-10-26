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
| Audit log | `app/Libraries/AuditLog.php`, `audit_events` table | Records staff sign-in now; publishing and restores later |
| Health check | `GET /health` | For monitoring; returns status only |
| Demo data | `app/Database/Seeds/DemoSeeder.php` | Labelled fictional data, blocked in production |

## Modules planned for later gates (proposed)

| Gate | Module |
| --- | --- |
| 3 | Content (pages, revisions, publishing log, restore) |
| 3 | News / Press (news, statements, tags, gallery, social links), Archives |
| 3 | Missions (mission records, country coverage, country finder, profiles) |
| 3 | About MFA&IC, About South Sudan, Services |
| 4 | Contacts (public inquiry form and Public Inquiries Inbox) |
| 4 | Diaspora (registration, referral to missions, contributions, newsletter, Diaspora inbox) |
| 4 | Notifications (rules per submission type, queued email) |

Each module will live under `app/Modules/<Name>/` with its own controllers,
models, views, migrations and tests, and will use the shared permission and
audit services rather than its own.

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
