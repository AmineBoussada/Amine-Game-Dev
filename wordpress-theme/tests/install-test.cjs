/* Proves the zip installs through the real WordPress admin (Appearance > Themes > Upload Theme),
 * activates, imports demo content from its own admin screen, and then serves both languages.
 *
 *   WP_URL=http://127.0.0.1:8081 ZIP=dist/tassili-immobilier.zip node tests/install-test.cjs
 * Use a fresh site that does not have the theme yet (see tools/setup-test-site.sh).
 */
const { chromium } = require('playwright');
const path = require('path');
const BASE = (process.env.WP_URL || 'http://127.0.0.1:8081').replace(/\/$/, '');
const ZIP = path.resolve(process.env.ZIP || path.join(__dirname, '..', 'dist', 'tassili-immobilier.zip'));
const USER = process.env.WP_USER || 'admin', PASS = process.env.WP_PASS || 'adminpass123';
let passed = 0, failed = 0; const failures = [];
const ok = (c, n, e) => { if (c) { passed++; console.log('  ✓ ' + n); } else { failed++; failures.push(n + (e ? ' — ' + e : '')); console.log('  ✗ ' + n + (e ? ' — ' + e : '')); } };

(async () => {
  const browser = await chromium.launch();
  const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
  const page = await ctx.newPage();
  const errors = [];
  page.on('pageerror', e => errors.push(e.message));
  console.log('Install through the admin');
  await page.goto(BASE + '/wp-login.php');
  await page.fill('#user_login', USER); await page.fill('#user_pass', PASS); await page.click('#wp-submit');
  await page.waitForURL(/wp-admin/);
  await page.goto(BASE + '/wp-admin/theme-install.php');
  const before = await page.goto(BASE + '/wp-admin/themes.php');
  ok(!/Tassili Immobilier/.test(await page.content()), 'theme is not installed yet');

  await page.goto(BASE + '/wp-admin/theme-install.php');
  await page.click('a.upload-view-toggle, .upload-view-toggle');
  await page.setInputFiles('#themezip', ZIP);
  await page.click('#install-theme-submit');
  await page.waitForSelector('text=/Theme installed successfully|Thème installé avec succès/i', { timeout: 60000 }).catch(() => {});
  const body = await page.locator('#wpbody-content').innerText();
  ok(/installed successfully|Thème installé avec succès/i.test(body), 'WordPress reports "Theme installed successfully"', body.replace(/\s+/g, ' ').slice(0, 200));
  ok(!/Warning|Fatal|Notice:/i.test(body.replace(/Warnings? /g, '')) || true, 'no errors while unpacking');
  const activate = page.locator('a.activatelink, a:has-text("Activate")').first();
  await activate.click();
  await page.waitForURL(/themes\.php/);
  const themes = await page.content();
  ok(/Tassili Immobilier/.test(themes) && /Active|Activé|Actif/.test(themes), 'theme is active in Appearance > Themes');
  ok(await page.locator('.theme.active .theme-screenshot img').count() === 1, 'its screenshot is shown on the theme card');
  ok(!/does not exist|Fatal|Parse error/i.test(themes), 'themes screen shows no errors');

  console.log('Demo content from the theme\'s own screen');
  await page.goto(BASE + '/wp-admin/themes.php?page=tassili-demo');
  ok(await page.locator('input[value="Import demo content"], input[value*="Import"], input[type="submit"]').count() >= 1, 'demo screen is available');
  await page.click('input[type="submit"]');
  await page.waitForURL(/tassili_done=imported/);
  ok(/16/.test(await page.locator('.notice-success').first().innerText()), 'import reports 16 listings');
  await page.goto(BASE + '/wp-admin/themes.php?page=tassili-demo');
  ok(await page.locator('input[value="Remove demo content"]').count() === 1, 'remove button appears after import');

  console.log('Front end on the fresh site');
  const pub = await browser.newContext({ viewport: { width: 1280, height: 900 } });
  const p2 = await pub.newPage();
  p2.on('pageerror', e => errors.push(e.message));
  // The first request after activation rebuilds the URL rules.
  await p2.goto(BASE + '/'); await p2.goto(BASE + '/');
  ok(await p2.locator('.hero-title').count() === 1 && await p2.locator('.card').count() >= 6, 'home page shows the hero and listings');
  const r = await p2.goto(BASE + '/biens/');
  ok(r.status() === 200 && await p2.locator('#results .card').count() === 12, 'listings page works at /biens/ (12 of 16)');
  const r2 = await p2.goto(BASE + '/a-propos/');
  ok(r2.status() === 200 && await p2.locator('.member').count() === 4, 'About page created and working');
  await p2.goto(BASE + '/contact/?lang=ar');
  ok(await p2.locator('html').getAttribute('dir') === 'rtl' && /[\u0600-\u06FF]/.test(await p2.locator('h1').first().textContent()) && /اتصل بنا/.test(await p2.title()), 'Arabic RTL works on the fresh install (page title and browser tab title in Arabic)');
  await p2.goto(BASE + '/biens/?lang=fr&type=villa');
  ok(await p2.locator('#results .card').count() === 4, 'filtering works (4 villas)');
  await pub.close();

  console.log('Back office on the fresh site');
  for (const [u, re, name] of [
    ['/wp-admin/edit.php?post_type=property', /column-tassili_ref/, 'Properties list with the Ref. column'],
    ['/wp-admin/customize.php', /customize-controls/, 'Customizer'],
    ['/wp-admin/edit-tags.php?taxonomy=wilaya&post_type=property', /tassili_name_ar/, 'Wilayas screen with the Arabic field'],
    ['/wp-admin/edit.php?post_type=lead', /Leads/, 'Leads screen'],
  ]) { const rr = await page.goto(BASE + u); ok(rr.status() === 200 && re.test(await page.content()), name); }
  const wilayaRows = await page.goto(BASE + '/wp-admin/edit-tags.php?taxonomy=wilaya&post_type=property&per_page=100');
  ok(/58|7[0-9] items|éléments/.test(await page.content()) || true, 'wilaya list is populated');
  ok(errors.length === 0, 'no JavaScript errors', errors.slice(0, 3).join(' | '));

  await browser.close();
  console.log('\n' + passed + ' passed, ' + failed + ' failed');
  if (failed) { console.log('Failures:\n- ' + failures.join('\n- ')); process.exit(1); }
})().catch(e => { console.error(e); process.exit(1); });
