# Configuration

All server-specific settings live in `.env`, copied from the `env` template.
`.env` is never committed. Everything else is in `app/Config/`.

## `.env` settings

| Setting | Development | Staging (VPS) and live (on-premises) |
| --- | --- | --- |
| `CI_ENVIRONMENT` | `development` | `production` |
| `app.baseURL` | `http://localhost:8080/` | The site's HTTPS address, ending in `/` |
| `app.forceGlobalSecureRequests` | unset | `true` (redirects HTTP to HTTPS) |
| `cookie.secure` | unset | `true` (cookies only over HTTPS) |
| `encryption.key` | generated | generated separately on each server with `php spark key:generate` |
| `database.default.*` | local database | the server's MySQL 8.4 database, `utf8mb4` |
| `email.*` | optional | SMTP relay. Required, because staff sign-in codes are emailed |
| `assets.bootstrapSource` | `local` | `cdn` on the VPS while the initial phase needs it; `local` on the on-premises server (PD-17) |
| `hosting.*` | unset | unset. Only for hosting without SSH or cron, such as the InfinityFree test copy (`Config\Hosting`, infinityfree.md) |
| `DEMO_PASSWORD` | optional | never set |

Staging uses `production` so it behaves exactly like the live site, including
error pages that hide technical details.

## Application settings in `app/Config/`

| File | What it controls |
| --- | --- |
| `Navigation.php` | The approved top navigation, the missions hierarchy and where ARCHIVES is linked (`$archivesPlacement`: `footer` or `primary`) |
| `Languages.php` | Public languages and their text direction. English is the only launch language |
| `App.php` | `supportedLocales`, `defaultLocale`, Content Security Policy switch |
| `AuthGroups.php` | The four staff roles and their permissions |
| `Auth.php` | Sign-in rules: no public registration, emailed sign-in code, 12-character minimum password |
| `Filters.php` | CSRF protection, secure headers, sign-in requirement for `/admin` |
| `Session.php`, `Cookie.php`, `Security.php` | Session and cookie names, CSRF token name |
| `ContentTypes.php` | Every editable content type: table, fields, validation, scope, whether it keeps versions or takes media; article and mission categories; the *Minister's engagements* tag |
| `Submissions.php` | Submission types and their inboxes, inbox statuses (PD-05), contribution categories, which inquiry and registration fields are required (PD-08), which text blocks open each form, spam limits (PD-11) |
| `Assets.php` | Where Bootstrap is loaded from (`local` or `cdn`, PD-17), the CDN address and the integrity hash of each file |
| `Hosting.php` | For hosts without SSH or cron: the `/maintenance` token, sending email during requests, an upload cap. All off by default |
| `Media.php` | Allowed file types and sizes for uploads (PD-12), and which kinds may link to an external address |

## Adding a language later

1. Add an entry to `Config\Languages::$languages`, with `dir` set to `ltr` or `rtl`.
2. Add the code to `Config\App::$supportedLocales`.
3. Add Ministry-approved translations in `app/Language/<code>/`.

Right-to-left languages automatically get `dir="rtl"` and Bootstrap's RTL stylesheet.
What visitors see when a page has no translation is still to be decided.
