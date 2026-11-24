/**
 * End-to-end smoke test of a generated theme on a running WordPress (Playwright).
 *
 *   node smoke-test.cjs --base http://localhost:8080 --prefix atlas --user admin --pass <pw> [--out ./shots]
 *
 * Requires: theme activated, `wp <prefix> seed` run, permalinks set to /%postname%/.
 * Covers the visitor side (pages, search, filters, favorites, gallery, contact validation)
 * and the owner workflow (add with photos, edit price/rooms, "Vendu", settings, lead, trash).
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');
const os = require('os');

const arg = (k, d) => { const i = process.argv.indexOf(`--${k}`); return i > -1 ? process.argv[i + 1] : d; };
const B = arg('base', 'http://localhost:8080').replace(/\/$/, '');
const P = arg('prefix');
const USER = arg('user', 'admin');
const PASS = arg('pass');
const OUT = arg('out', fs.mkdtempSync(path.join(os.tmpdir(), 'theme-smoke-')));
if (!P || !PASS) { console.error('Usage: smoke-test.cjs --prefix <prefix> --pass <password> [--base URL] [--user admin] [--out dir]'); process.exit(2); }
fs.mkdirSync(OUT, { recursive: true });

let failed = 0;
const ok = (c, m) => { console.log((c ? 'PASS ' : 'FAIL ') + m); if (!c) failed++; };
const TITLE = 'Appartement F4 test automatique';

(async () => {
  const br = await chromium.launch();
  const ctx = await br.newContext({ viewport: { width: 1440, height: 1000 } });
  const errs = [];
  ctx.on('page', (pg) => pg.on('pageerror', (e) => errs.push(e.message)));
  const p = await ctx.newPage();
  p.on('pageerror', (e) => errs.push(e.message));

  // ---- Test photos (rendered locally, no network needed)
  const photos = [];
  for (const [i, color] of [['1', '#7a8f7c'], ['2', '#b48c64']]) {
    await p.setContent(`<div style="width:1200px;height:800px;background:linear-gradient(135deg,${color},#f1ece2)"></div>`);
    const file = path.join(OUT, `test-photo-${i}.png`);
    await p.locator('div').screenshot({ path: file });
    photos.push(file);
  }

  // ---- Visitor side
  for (const u of ['/', '/biens/', '/biens/?transaction=location', '/contact/', '/a-propos/', '/favoris/', '/politique-de-confidentialite/']) {
    const r = await p.goto(B + u);
    const html = await p.content();
    ok(r.status() === 200 && !/(Warning|Notice|Fatal error|Deprecated)<\/b>:/.test(html) && (await p.locator('h1').count()) === 1, `page ${u}`);
  }
  await p.goto(B + '/');
  await p.screenshot({ path: path.join(OUT, 'home.png') });
  ok((await p.locator('img[data-scene]').count()) === 0, 'illustrations rendered');
  await p.click('.search-bar__tabs label:has-text("Louer")');
  await p.click('.search-bar__submit');
  await p.waitForURL(/\/biens\//);
  ok(p.url().includes('transaction=location'), 'home search → listings');
  await p.goto(B + '/biens/');
  const total = await p.locator('.property-card').count();
  await p.locator('.segmented label:has-text("Vente")').click();
  await p.waitForURL(/transaction=vente/);
  ok((await p.locator('.property-card').count()) <= total, 'transaction filter');
  await p.selectOption('.sort-select select', 'price-asc');
  await p.waitForURL(/tri=price-asc/);
  const nums = (await p.locator('.property-card__price').allInnerTexts()).map((t) => Number(t.replace(/\D/g, '')));
  ok(nums.every((v, i) => !i || nums[i - 1] <= v), 'sort by price');
  if (total) {
    await p.locator('.property-card [data-fav]').first().click();
    ok((await p.locator('[data-fav-count]').innerText()) === '1', 'favorite counter');
    await p.goto(B + '/favoris/');
    ok((await p.locator('.property-card').count()) === 1, 'favorites page');
  }
  await p.goto(B + '/contact/');
  await p.click('.contact-form__submit');
  ok((await p.locator('.field.has-error').count()) >= 5, 'contact form validation');

  // ---- Owner workflow
  await p.goto(B + '/wp-login.php');
  await p.fill('#user_login', USER);
  await p.fill('#user_pass', PASS);
  await p.click('#wp-submit');
  await p.waitForURL(/wp-admin/);
  await p.goto(B + '/wp-admin/post-new.php?post_type=property');
  await p.fill('#title', TITLE);
  await p.click('#content-html');
  await p.fill('#content', 'Appartement créé par le test automatique du thème.');
  await p.fill('#excerpt', 'Bien de test.');
  const f = (k) => `#${P}-field-${k}`;
  await p.selectOption(f('transaction'), 'vente');
  await p.selectOption(f('type'), 'appartement');
  await p.fill(f('typology'), 'F4');
  await p.fill(f('price'), '27500000');
  ok((await p.locator('[data-price-preview]').innerText()).includes('27 500 000 DA'), 'admin price preview');
  await p.check(f('featured'));
  await p.fill(f('commune'), 'Centre-ville');
  await p.fill(f('surface'), '135');
  await p.fill(f('rooms'), '4');
  await p.check('input[value="Ascenseur"]');
  await p.click('[data-gallery-add]');
  await p.waitForSelector('.media-modal');
  await p.locator('.media-modal input[type=file]').first().setInputFiles(photos);
  await p.waitForFunction(() => document.querySelectorAll('.media-modal .attachments .attachment.selected').length >= 2 && !document.querySelector('.media-modal .uploading'), null, { timeout: 60000 });
  await p.click('.media-modal .media-button-select');
  ok((await p.locator('[data-gallery-list] li').count()) === 2, 'photos added to the gallery box');
  await p.click('#publish');
  await p.waitForSelector('#message');
  const ref = await p.inputValue(f('reference'));
  ok(/^[A-Z0-9]+-\d+$/.test(ref), `automatic reference ${ref}`);
  const url = await p.locator('#sample-permalink a').first().getAttribute('href');

  const v = await ctx.newPage();
  await v.goto(url);
  ok((await v.locator('.detail__price').innerText()).includes('27 500 000 DA'), 'price on property page');
  ok((await v.locator('.gallery__thumb').count()) === 2, 'uploaded photos in gallery');
  await v.screenshot({ path: path.join(OUT, 'property.png') });
  await v.goto(B + '/');
  ok((await v.locator('.property-grid').innerText()).includes(TITLE), 'featured on homepage');

  await p.fill(f('price'), '25900000');
  await p.fill(f('rooms'), '5');
  await p.selectOption(f('status'), 'vendu');
  await p.click('#publish');
  await p.waitForSelector('#message');
  await v.goto(url);
  ok((await v.locator('.detail__price').innerText()).includes('25 900 000 DA'), 'edited price on front');
  ok((await v.locator('.detail__badges').innerText()).toLowerCase().includes('vendu'), '"Vendu" badge');

  await p.goto(B + `/wp-admin/admin.php?page=${P}-settings`);
  const phoneBefore = await p.inputValue(`#${P}-phone`);
  await p.fill(`#${P}-phone`, '+213 21 99 88 77');
  await p.click('#submit');
  await p.waitForSelector('#setting-error-settings_updated');
  await v.goto(B + '/contact/');
  ok((await v.locator('.site-header').innerText()).includes('+213 21 99 88 77'), 'settings update header phone');
  await p.goto(B + `/wp-admin/admin.php?page=${P}-settings`);
  await p.fill(`#${P}-phone`, phoneBefore);
  await p.click('#submit');

  await v.goto(url);
  await v.click('.agent-card [data-request-visit]');
  await v.waitForTimeout(700);
  await v.fill('#formulaire input[name=name]', 'Client Test');
  await v.fill('#formulaire input[name=phone]', '0661 22 33 44');
  await v.fill('#formulaire input[name=email]', 'client@example.com');
  await v.check('#formulaire input[name=consent]');
  await v.click('#formulaire button[type=submit]');
  await v.waitForSelector('.form-success');
  await p.goto(B + `/wp-admin/edit.php?post_type=${P}_lead`);
  ok((await p.locator('.wp-list-table').innerText()).includes(`Client Test — Demande de visite (${ref})`), 'lead saved in admin');

  await p.goto(B + '/wp-admin/edit.php?post_type=property');
  const row = p.locator('tr', { hasText: ref });
  await row.hover();
  await row.locator('.submitdelete').click();
  await p.waitForSelector('#message');
  ok((await v.goto(url)).status() === 404, 'trashed property is offline');

  ok(errs.length === 0, 'no JavaScript errors' + (errs.length ? ': ' + errs.join(' | ') : ''));
  await br.close();
  console.log(`\nScreenshots: ${OUT}`);
  console.log(failed ? `${failed} check(s) failed.` : 'All checks passed.');
  process.exit(failed ? 1 : 0);
})().catch((e) => { console.error(e); process.exit(1); });
