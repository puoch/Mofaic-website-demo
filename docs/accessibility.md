# Accessibility and browser checks

Target: WCAG 2.2 level AA (decided 26 October 2026, PD-14).

## Automated checks

`tests/browser/check.mjs` opens the site in Chromium (Playwright) and follows
links from the home page. It is a development tool: it needs Node.js, runs on a
developer machine or the staging server, and nothing in it is served by the
website.

For every page it:

- runs [axe-core](https://github.com/dequelabs/axe-core) with the WCAG 2.0, 2.1
  and 2.2 level A and AA rules;
- blocks every request to another host and reports it (the site must work with
  outside internet blocked);
- records the HTTP status, console errors and page weight.

On the home page and a long missions page it also checks that the skip link is
the first Tab stop and moves focus to the main content, and that the header
stays at the top after scrolling (brief section 1). At 320 and 375 pixels wide
it checks that nothing scrolls sideways (WCAG 1.4.10 Reflow) and that the
menu button opens the menu from the keyboard.

```bash
cd tests/browser
npm ci
BASE_URL=http://localhost:8080 npm run check
```

Run it against a copy in production mode (`CI_ENVIRONMENT = production`), so
the debug toolbar is not on the page, and with `app.baseURL` matching
`BASE_URL`. Results go to `tests/browser/report/` (not committed).

Staff pages are checked too when a **local** test account is given. The sign-in
code is emailed, and a local copy usually has no mail server, so
`ADMIN_CODE_COMMAND` must print the newest code. For the SQLite development
copy:

```bash
export ADMIN_EMAIL=demo-ministryadmin@example.invalid ADMIN_PASSWORD='<demo password>'
export ADMIN_CODE_COMMAND='php -r '"'"'echo (new SQLite3("../../writable/mofaic_dev.db"))->querySingle("SELECT secret FROM auth_identities WHERE type = \"email_2fa\" ORDER BY id DESC LIMIT 1");'"'"
BASE_URL=http://localhost:8080 npm run check
```

The demo password is printed when `DemoSeeder` runs (or set `DEMO_PASSWORD`).
Never point the staff part at the live site.

## Gate 5 results (27 October 2026)

Full report: [evidence/gate-5/browser-check.md](evidence/gate-5/browser-check.md).
Run on a production-mode copy with SQLite and demonstration data, with all
public forms opened by the demo text blocks.

| Check | Result |
| --- | --- |
| axe-core 4.14.0, WCAG 2.0/2.1/2.2 A and AA rules | No violations on 41 public pages and 52 staff pages (a second run as Super Administrator covered 80 pages, also none) |
| Requests to other hosts | None |
| Skip link, fixed header | Pass |
| Mobile menu by keyboard, no sideways scrolling at 320 and 375 px | Pass on the home, CONTACTS, diaspora registration and missions pages |

Found and fixed during Gate 5 (first runs used axe-core 4.10.3; the final runs above used 4.14.0):

1. **Bootstrap was missing from the repository.** The Composer rule `vendor/`
   in `.gitignore` also matched `public/assets/vendor/`, so Bootstrap's CSS and
   JavaScript were never committed. A fresh clone had no styles and no working
   mobile menu. The rule is now `/vendor/`, the files are committed (checked
   against the npm package `bootstrap@5.3.8`: identical SHA-256), and
   `tests/unit/LocalAssetsTest.php` fails if a file the layouts load is missing.
2. **Link colour contrast.** Bootstrap's link blue is 4.1:1 on the light footer
   band, below the 4.5:1 minimum. Links now use the site accent, 7.2:1 on that
   band and 7.8:1 on white (proposal G5-05: the brand colours are still
   deferred item 12).

axe marks some colour contrast results as "needs review" because it cannot
compute them: the country drop-down (Bootstrap draws its arrow with a
background image) and text areas partly scrolled out of view. Those are dark
text on white and were checked by eye.

## Not covered by automated checks

Automated tools find only part of the WCAG failures. Before launch, a person
should check, on the staging copy with real content:

- Screen readers: NVDA with Firefox on Windows, and VoiceOver on iOS, through
  the home page, a news article, the mission finder and each public form,
  including its error messages.
- Keyboard only, through every public form and the main staff tasks
  (publish a page, handle an inquiry), with focus always visible.
- Zoom to 200% and 400%, and text spacing (WCAG 1.4.12).
- Content written by staff: headings in order, meaningful link text, alt text
  on every image, captions or transcripts for audio and video, plain language.
  This depends on the editors, so it is part of their training
  ([operations.md](operations.md)).
- Firefox and Safari. The checks above ran in Chromium only.
