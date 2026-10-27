// Browser checks for MOFAIC WEB public pages. Run against a local copy:
//
//   BASE_URL=http://localhost:8081 npm run check
//
// For each page found by following links from the home page it runs axe-core
// with the WCAG 2.0/2.1/2.2 A and AA rules, blocks and records any request
// to another host (the site must work with outside internet blocked) and
// records the page weight. It also checks the fixed header, the skip link,
// the mobile menu and horizontal scrolling at 320px.
//
// Writes report/report.json and report/report.md. Exits 1 when a check fails.
// Automated checks find only part of the WCAG problems; the manual checks in
// docs/accessibility.md are still needed.

import { chromium } from 'playwright';
import { readFileSync, mkdirSync, writeFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import { execSync } from 'node:child_process';

const require = createRequire(import.meta.url);
const axeSource = readFileSync(require.resolve('axe-core/axe.min.js'), 'utf8');
const axeVersion = require('axe-core/package.json').version;

const base = new URL(process.env.BASE_URL ?? 'http://localhost:8081');
const startPath = process.env.START_PATH ?? '/en';
const maxPages = Number(process.env.MAX_PAGES ?? 150);
const extraPaths = ['/login'];
const tags = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'];

const browser = await chromium.launch();
// bypassCSP lets axe be injected; the site's own Content-Security-Policy
// still blocks inline scripts for visitors.
const context = await browser.newContext({ viewport: { width: 1280, height: 900 }, bypassCSP: true });
const outside = [];

await context.route('**/*', (route) => {
  const url = new URL(route.request().url());
  if (url.host === base.host || url.protocol === 'data:') {
    return route.continue();
  }
  outside.push({ from: route.request().frame()?.url() ?? '', url: url.href });
  return route.abort('blockedbyclient');
});

const failures = [];
const pages = [];

function fail(check, page, detail) {
  failures.push({ check, page, detail });
}

// Links to follow: same host, under one of the prefixes, no files and never
// the sign-out link (a GET that ends the session).
function normalise(href, prefixes) {
  const url = new URL(href, base);
  if (url.host !== base.host) return null;
  url.hash = '';
  url.search = '';
  if (!prefixes.some((prefix) => url.pathname === prefix || url.pathname.startsWith(prefix + '/'))) return null;
  if (/\/logout$/.test(url.pathname)) return null;
  if (/\.(pdf|jpe?g|png|webp|gif|svg|docx?|xlsx?)$/i.test(url.pathname)) return null;
  return url.pathname;
}

// --- Crawl, axe, outside requests and weight --------------------------------
async function crawl(starts, prefixes, area) {
  const queue = [...starts];
  const seen = new Set(queue);
  // Pages that differ only by record id share one template: check two of each.
  const shapes = {};
  let count = 0;

  while (queue.length > 0 && count < maxPages) {
    const path = queue.shift();
    count += 1;
    const page = await context.newPage();
    let bytes = 0;
    let requests = 0;
    page.on('response', async (response) => {
      requests += 1;
      try {
        bytes += (await response.body()).length;
      } catch {
        // Redirects have no body.
      }
    });
    const consoleErrors = [];
    page.on('console', (msg) => msg.type() === 'error' && !msg.text().includes('ERR_BLOCKED_BY_CLIENT') && consoleErrors.push(msg.text()));

    const response = await page.goto(new URL(path, base).href, { waitUntil: 'load' });
    const status = response?.status() ?? 0;
    await page.waitForLoadState('networkidle').catch(() => {});

    if (status >= 400) fail('http-status', path, `HTTP ${status}`);
    if (area === 'admin' && new URL(page.url()).pathname === '/login') fail('admin-session', path, 'redirected to sign-in');
    for (const text of consoleErrors) fail('console-error', path, text);

    await page.evaluate(axeSource);
    const result = await page.evaluate(async (runTags) => {
      // eslint-disable-next-line no-undef
      const r = await axe.run(document, { runOnly: { type: 'tag', values: runTags }, resultTypes: ['violations', 'incomplete'] });
      return {
        violations: r.violations.map((v) => ({ id: v.id, impact: v.impact, help: v.help, nodes: v.nodes.map((n) => n.target.join(' ')).slice(0, 5) })),
        incomplete: r.incomplete.map((v) => v.id),
      };
    }, tags);

    for (const v of result.violations) fail('axe', path, `${v.id} (${v.impact}): ${v.help} at ${v.nodes.join(', ')}`);

    const links = await page.$$eval('a[href]', (as) => as.map((a) => a.getAttribute('href')));
    for (const href of links) {
      const next = normalise(href, prefixes);
      const shape = next?.replace(/\/\d+(?=\/|$)/g, '/N');
      if (next && !seen.has(next) && (shapes[shape] ?? 0) < 2) {
        seen.add(next);
        shapes[shape] = (shapes[shape] ?? 0) + 1;
        queue.push(next);
      }
    }

    pages.push({ area, path, status, requests, kilobytes: Math.round(bytes / 1024), violations: result.violations.length, needsReview: [...new Set(result.incomplete)] });
    await page.close();
  }
}

await crawl([startPath, ...extraPaths], [startPath, ...extraPaths], 'public');

// Staff pages, only when a local test account is given. The sign-in code is
// emailed, so ADMIN_CODE_COMMAND must print the latest code (for example by
// reading the local development database). Never point this at the live site.
let adminChecked = false;
if (process.env.ADMIN_EMAIL && process.env.ADMIN_PASSWORD && process.env.ADMIN_CODE_COMMAND) {
  const page = await context.newPage();
  await page.goto(new URL('/login', base).href);
  await page.fill('input[name="email"]', process.env.ADMIN_EMAIL);
  await page.fill('input[name="password"]', process.env.ADMIN_PASSWORD);
  await Promise.all([page.waitForNavigation(), page.click('button[type="submit"]')]);
  if (new URL(page.url()).pathname.startsWith('/auth/a/')) {
    // Shield creates the code when it shows this page. A local copy usually
    // has no mail server, so post the code to the verify step directly.
    const code = execSync(process.env.ADMIN_CODE_COMMAND, { encoding: 'utf8' }).trim();
    await page.evaluate((token) => {
      const form = document.querySelector('form');
      form.action = '/auth/a/verify';
      const input = document.createElement('input');
      input.name = 'token';
      input.value = token;
      form.append(input);
    }, code);
    await Promise.all([page.waitForNavigation(), page.evaluate(() => document.querySelector('form').submit())]);
  }
  await page.close();
  await crawl(['/admin'], ['/admin'], 'admin');
  adminChecked = true;
}

// --- Layout and keyboard checks on a long page and the home page ------------
async function layoutChecks(path) {
  const page = await context.newPage();
  await page.goto(new URL(path, base).href);

  // Skip link is the first Tab stop and moves focus to the main content.
  await page.keyboard.press('Tab');
  const first = await page.evaluate(() => ({ cls: document.activeElement?.className ?? '', href: document.activeElement?.getAttribute('href') }));
  if (!String(first.cls).includes('skip-link') || first.href !== '#main') fail('skip-link', path, `first Tab stop was ${JSON.stringify(first)}`);
  await page.keyboard.press('Enter');
  const focused = await page.evaluate(() => document.activeElement?.id);
  if (focused !== 'main') fail('skip-link', path, `Enter on the skip link focused #${focused}`);

  // Header stays at the top after scrolling.
  await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
  await page.waitForTimeout(100);
  const top = await page.$eval('[data-testid="site-header"]', (h) => h.getBoundingClientRect().top);
  const scrolled = await page.evaluate(() => window.scrollY);
  if (scrolled > 0 && Math.abs(top) > 1) fail('fixed-header', path, `header top is ${top}px after scrolling ${scrolled}px`);
  await page.close();
}

async function mobileChecks(path, width) {
  const mobile = await browser.newContext({ viewport: { width, height: 740 } });
  await mobile.route('**/*', (route) => (new URL(route.request().url()).host === base.host ? route.continue() : route.abort()));
  const page = await mobile.newPage();
  await page.goto(new URL(path, base).href);

  const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
  if (overflow > 0) fail('reflow', `${path} @${width}px`, `page scrolls sideways by ${overflow}px`);

  const toggle = page.locator('.navbar-toggler');
  if (await toggle.count() === 0 || !(await toggle.isVisible())) {
    fail('mobile-menu', `${path} @${width}px`, 'no visible menu button');
  } else {
    await toggle.focus();
    await page.keyboard.press('Enter');
    await page.waitForTimeout(500);
    const expanded = await toggle.getAttribute('aria-expanded');
    const visibleLinks = await page.locator('#primary-nav a:visible, .navbar-collapse a:visible').count();
    if (expanded !== 'true' || visibleLinks === 0) fail('mobile-menu', `${path} @${width}px`, `menu did not open with the keyboard (aria-expanded=${expanded}, visible links=${visibleLinks})`);
  }
  await mobile.close();
}

const longPage = pages.find((p) => p.path.startsWith('/en/missions'))?.path ?? startPath;
for (const path of [startPath, longPage]) {
  await layoutChecks(path);
}
for (const path of [startPath, '/en/contacts', '/en/diaspora/register', longPage]) {
  await mobileChecks(path, 320);
  await mobileChecks(path, 375);
}

await browser.close();

for (const o of outside) fail('outside-request', o.from, o.url);

// --- Report ------------------------------------------------------------------
const report = {
  baseUrl: base.href,
  ranAt: new Date().toISOString(),
  axeVersion,
  axeTags: tags,
  adminChecked,
  pages,
  outsideRequests: outside,
  failures,
};

const dir = new URL('./report/', import.meta.url);
mkdirSync(dir, { recursive: true });
writeFileSync(new URL('report.json', dir), JSON.stringify(report, null, 2));

const md = [
  `# Browser check report`,
  '',
  `- Base URL: ${base.href}`,
  `- Run at: ${report.ranAt}`,
  `- axe-core ${axeVersion}, rules: ${tags.join(', ')}`,
  `- Pages checked: ${pages.length} (staff pages ${adminChecked ? 'included' : 'not checked: no local test account given'})`,
  `- Requests to other hosts: ${outside.length}`,
  `- Failures: ${failures.length}`,
  '',
  '| Area | Page | HTTP | Requests | KB (uncompressed) | axe violations | Needs manual review |',
  '|---|---|---|---|---|---|---|',
  ...pages.map((p) => `| ${p.area} | ${p.path} | ${p.status} | ${p.requests} | ${p.kilobytes} | ${p.violations} | ${p.needsReview.join(', ')} |`),
  '',
  '## Failures',
  '',
  ...(failures.length ? failures.map((f) => `- **${f.check}** ${f.page}: ${f.detail}`) : ['None.']),
  '',
];
writeFileSync(new URL('report.md', dir), md.join('\n'));

console.log(`${pages.length} pages, ${outside.length} outside requests, ${failures.length} failures`);
for (const f of failures) console.log(`FAIL ${f.check} ${f.page}: ${f.detail}`);
process.exit(failures.length ? 1 : 0);
