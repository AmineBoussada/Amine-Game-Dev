/* Upgrade path: a site that already runs an older version of the theme receives the new zip through
 * Appearance > Themes > Upload Theme, replaces the old files, and keeps its properties.
 *
 *   OLD_ZIP=/path/old.zip NEW_ZIP=dist/tassili-immobilier-1.1.0.zip WP_URL=http://127.0.0.1:8081 node tests/upgrade-test.cjs
 * Needs a fresh site without the theme (tools/setup-test-site.sh).
 */
const { chromium } = require('playwright');
const path = require('path');
const BASE = (process.env.WP_URL || 'http://127.0.0.1:8081').replace(/\/$/, '');
const dist = path.join(__dirname, '..', 'dist');
const NEW = path.resolve(process.env.NEW_ZIP || path.join(dist, require('fs').readdirSync(dist).filter(f => /\.zip$/.test(f)).sort().pop()));
const OLD = path.resolve(process.env.OLD_ZIP);
let passed = 0, failed = 0; const failures = [];
const ok = (c, n, e) => { if (c) { passed++; console.log('  ✓ ' + n); } else { failed++; failures.push(n + (e ? ' — ' + e : '')); console.log('  ✗ ' + n + (e ? ' — ' + e : '')); } };

async function upload(page, zip) {
  await page.goto(BASE + '/wp-admin/theme-install.php');
  await page.click('.upload-view-toggle');
  await page.setInputFiles('#themezip', zip);
  await page.click('#install-theme-submit');
}
(async () => {
  const browser = await chromium.launch();
  const page = await (await browser.newContext({ viewport: { width: 1280, height: 900 } })).newPage();
  await page.goto(BASE + '/wp-login.php');
  await page.fill('#user_login', 'admin'); await page.fill('#user_pass', 'adminpass123'); await page.click('#wp-submit'); await page.waitForURL(/wp-admin/);

  console.log('Old version installed and in use');
  await upload(page, OLD);
  await page.waitForSelector('a.activatelink, a:has-text("Activate")', { timeout: 60000 });
  await page.locator('a.activatelink, a:has-text("Activate")').first().click();
  await page.waitForURL(/themes\.php/);
  await page.goto(BASE + '/wp-admin/themes.php?page=tassili-demo');
  await page.click('input[type="submit"]');
  await page.waitForURL(/tassili_done=imported/);
  await page.goto(BASE + '/wp-admin/index.php');
  const oldMenu = await page.locator('#adminmenu').innerText();
  ok(!/Agency|Agence/.test(oldMenu), 'old version: there is no "Agency" menu yet');
  ok(await page.locator('#adminmenu li.toplevel_page_tassili').count() === 1 || await page.locator('#menu-posts-property').count() === 1, 'old version: a Properties menu exists');
  const countBefore = await (await page.goto(BASE + '/wp-admin/edit.php?post_type=property')).status();

  console.log('Upload the new zip over it');
  await upload(page, NEW);
  await page.waitForSelector('text=/already installed|déjà installé/i', { timeout: 60000 });
  const compare = await page.locator('#wpbody-content').innerText();
  ok(/1\.0\.0/.test(compare) && /1\.1\.0/.test(compare), 'WordPress shows the comparison: installed 1.0.0 vs uploaded 1.1.0', compare.replace(/\s+/g, ' ').slice(0, 220));
  ok(/already installed|déjà installé/i.test(compare) && await page.locator('a:has-text("Replace active with uploaded"), a:has-text("Remplacer")').count() >= 1, 'it stops on "This theme is already installed" and waits for the Replace button');
  await page.locator('a:has-text("Replace active with uploaded"), a:has-text("Remplacer")').first().click();
  await page.waitForSelector('text=/Theme updated successfully|Thème mis à jour|replaced|remplacé/i', { timeout: 60000 }).catch(() => {});

  console.log('After the replacement');
  await page.goto(BASE + '/wp-admin/index.php');
  const menu = await page.locator('#adminmenu').innerText();
  ok(/Agency/.test(menu), 'the new "Agency" menu appears');
  const propSub = (await page.locator('#menu-posts-property .wp-submenu li:not(.wp-submenu-head)').allInnerTexts()).map(t => t.trim());
  ok(propSub.join('|') === 'All properties|Add new property', 'Properties now holds just "All properties" and "Add new property"', propSub.join(' | '));
  ok(await page.locator('#menu-posts-property .dashicons-building').count() === 1 && await page.locator('#menu-posts-lead .dashicons-email-alt').count() === 1, 'building icon for properties, envelope icon for requests');
  await page.goto(BASE + '/wp-admin/admin.php?page=tassili');
  ok(/version 1\.1\.0/.test(await page.locator('.tassili-overview .description').first().innerText()), 'the overview shows "version 1.1.0"');
  await page.goto(BASE + '/wp-admin/edit.php?post_type=property');
  ok(await page.locator('#the-list tr.type-property').count() === 16, 'all 16 properties are still there');
  ok(await page.locator('th.column-tassili_status').count() >= 1, 'the list has the new availability column');
  const pub = await (await browser.newContext()).newPage();
  const r = await pub.goto(BASE + '/biens/');
  ok(r.status() === 200 && await pub.locator('#results .card').count() === 12, 'the public listings page still works');
  await browser.close();
  console.log('\n' + passed + ' passed, ' + failed + ' failed');
  if (failed) { console.log('Failures:\n- ' + failures.join('\n- ')); process.exit(1); }
})().catch(e => { console.error(e); process.exit(1); });
