// Checks PD-17's CDN mode. Run against a copy started with
// assets.bootstrapSource = cdn, at the address in app.baseURL:
//
//   BASE_URL=http://localhost:8080 node cdn-fallback.mjs
//
// Three cases: the CDN works (no local Bootstrap requests), the CDN returns
// a changed file (integrity check fails, local copy loads) and the CDN cannot
// be reached (local copy loads). Exits 1 when Bootstrap is missing in any.

import { chromium } from 'playwright';
import fs from 'node:fs';

const root = new URL('../../public/assets/vendor/bootstrap-5.3.8/', import.meta.url).pathname;
const base = process.env.BASE_URL ?? 'http://localhost:8080';
let failed = false;
const browser = await chromium.launch();
async function run(name, mode) {
  const ctx = await browser.newContext();
  const page = await ctx.newPage();
  const local = [], cdn = [], errors = [];
  page.on('console', m => { if (m.type() === 'error') errors.push(m.text().slice(0, 140)); });
  page.on('request', r => { const u = r.url(); if (u.includes('jsdelivr')) cdn.push(u.split('/').pop()); if (u.includes('/assets/vendor/bootstrap')) local.push(u.split('/').pop()); });
  if (mode === 'blocked') {
    await page.route('https://cdn.jsdelivr.net/**', route => route.abort('internetdisconnected'));
  } else {
    await page.route('https://cdn.jsdelivr.net/**', route => {
      const file = route.request().url().split('/dist/')[1];
      let body = fs.readFileSync(root + file);
      if (mode === 'tampered') body = Buffer.concat([body, Buffer.from('\n/* changed */')]);
      route.fulfill({ status: 200, body, headers: { 'content-type': file.endsWith('.css') ? 'text/css' : 'application/javascript', 'access-control-allow-origin': '*' } });
    });
  }
  await page.goto(base + '/en', { waitUntil: 'load' });
  await page.waitForTimeout(800);
  const r = await page.evaluate(() => ({
    bootstrapJs: typeof window.bootstrap !== 'undefined',
    bootstrapCss: (() => { const s = document.createElement('span'); s.className = 'visually-hidden'; document.body.append(s); const ok = getComputedStyle(s).position === 'absolute'; s.remove(); return ok; })(),
    sheets: [...document.querySelectorAll('link[rel=stylesheet]')].map(l => l.href.split('/').slice(2,3)[0] + ':' + (l.sheet ? 'ok' : 'none')),
  }));
  const ok = r.bootstrapJs && r.bootstrapCss && (mode === 'ok' ? local.length === 0 : local.length === 2);
  if (!ok) failed = true;
  console.log(ok ? 'PASS' : 'FAIL', name, JSON.stringify({ ...r, cdnRequests: cdn, localRequests: local, errors }));
  await ctx.close();
}
await run('CDN works   ', 'ok');
await run('CDN tampered', 'tampered');
await run('CDN blocked ', 'blocked');
await browser.close();
process.exit(failed ? 1 : 0);
