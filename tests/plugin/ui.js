// Admin UI checks for Top Solution Core. Usage: node ui.js <out-dir>
// Needs users "manager" (shop_manager) and "sub" (subscriber), created by users-setup.php.
const { chromium } = require('playwright');
const B = 'http://127.0.0.1:8080', OUT = process.argv[2] || '.';
let failed = 0;
const ok = (c, m) => { console.log((c ? 'PASS ' : 'FAIL ') + m); if (!c) failed++; };

async function login(ctx, user, pass) {
  const p = await ctx.newPage();
  await p.goto(B + '/wp-login.php', { waitUntil: 'load' });
  await p.fill('#user_login', user); await p.fill('#user_pass', pass);
  await Promise.all([p.waitForNavigation({ waitUntil: 'load' }), p.click('#wp-submit')]);
  return p;
}
(async () => {
  const b = await chromium.launch();

  // ---------------- administrator
  const a = await b.newContext({ viewport: { width: 1280, height: 900 } });
  const p = await login(a, 'admin', 'admin-pass');
  await p.goto(B + '/wp-admin/admin.php?page=top-solution', { waitUntil: 'load' });
  ok(await p.locator('h1:has-text("Top Solution")').count() === 1, 'admin: dashboard renders');
  ok(await p.locator('html[dir="rtl"]').count() === 1, 'admin: RTL layout');
  const rows = await p.locator('table.ts-status tbody tr').count();
  ok(rows === 9, `admin: status table has 9 rows (got ${rows})`);
  ok(await p.locator('#adminmenu a:has-text("Top Solution")').count() >= 1, 'admin: menu item present');
  ok(await p.locator('#adminmenu a[href*="top-solution-settings"]').count() === 1, 'admin: settings sub-page present');
  const css = await p.locator('link#ts-core-admin-css').count();
  ok(css === 1, 'admin: stylesheet loaded on plugin screens');
  await p.screenshot({ path: OUT + '/plugin-dashboard.png', fullPage: true });
  // every status badge carries text, not colour alone
  const badges = await p.locator('.ts-badge').allInnerTexts();
  ok(badges.length === 9 && badges.every(t => /[✓!✕]/.test(t) && t.trim().length > 2), 'admin: status badges have symbol + word (not colour only)');
  // stylesheet only on our screens
  await p.goto(B + '/wp-admin/index.php', { waitUntil: 'load' });
  ok(await p.locator('link#ts-core-admin-css').count() === 0, 'admin: stylesheet NOT loaded on other admin screens');

  // settings: orders tab
  await p.goto(B + '/wp-admin/admin.php?page=top-solution-settings&tab=orders', { waitUntil: 'load' });
  ok(await p.locator('input[name="ts_core_settings[initial_order_status]"][value="processing"]').isChecked(), 'settings: processing is the default');
  await p.check('input[value="on-hold"]');
  await Promise.all([p.waitForNavigation({ waitUntil: 'load' }), p.click('#submit')]);
  ok(await p.locator('.notice-success, #setting-error-settings_updated').count() >= 1, 'settings: save shows success notice');
  ok(await p.locator('input[value="on-hold"]').isChecked(), 'settings: on-hold persisted after save');
  await p.screenshot({ path: OUT + '/plugin-settings-orders.png', fullPage: true });

  // settings: general tab, checkbox on one tab must not touch the other tab
  await p.goto(B + '/wp-admin/admin.php?page=top-solution-settings&tab=general', { waitUntil: 'load' });
  await p.check('input[name="ts_core_settings[debug_logging]"]');
  await Promise.all([p.waitForNavigation({ waitUntil: 'load' }), p.click('#submit')]);
  await p.goto(B + '/wp-admin/admin.php?page=top-solution-settings&tab=orders', { waitUntil: 'load' });
  ok(await p.locator('input[value="on-hold"]').isChecked(), 'settings: saving General did not reset Orders');
  await p.goto(B + '/wp-admin/admin.php?page=top-solution-settings&tab=general', { waitUntil: 'load' });
  ok(await p.locator('input[name="ts_core_settings[debug_logging]"]').isChecked(), 'settings: debug logging persisted');
  await p.screenshot({ path: OUT + '/plugin-settings-general.png', fullPage: true });

  // forged value: tamper with the radio value in the browser, then save
  await p.goto(B + '/wp-admin/admin.php?page=top-solution-settings&tab=orders', { waitUntil: 'load' });
  await p.evaluate(() => { document.querySelector('input[value="on-hold"]').value = 'completed'; });
  await p.check('input[value="completed"]');
  await Promise.all([p.waitForNavigation({ waitUntil: 'load' }), p.click('#submit')]);
  await p.goto(B + '/wp-admin/admin.php?page=top-solution-settings&tab=orders', { waitUntil: 'load' });
  ok(await p.locator('input[value="processing"]').isChecked(), 'settings: forged status "completed" rejected, fell back to processing');

  // forged request without nonce
  const bad = await p.request.post(B + '/wp-admin/options.php', { form: { option_page: 'ts_core_settings_group', 'ts_core_settings[__tab]': 'orders', 'ts_core_settings[initial_order_status]': 'on-hold' }, failOnStatusCode: false });
  ok(bad.status() === 403 || (await bad.text()).includes('تم') === false, `settings: POST without nonce refused (HTTP ${bad.status()})`);
  await p.goto(B + '/wp-admin/admin.php?page=top-solution-settings&tab=orders', { waitUntil: 'load' });
  ok(await p.locator('input[value="processing"]').isChecked(), 'settings: forged POST without nonce changed nothing');

  // ---------------- shop manager
  const m = await b.newContext({ viewport: { width: 1280, height: 900 } });
  const mp = await login(m, 'manager', 'manager-pass');
  const r1 = await mp.goto(B + '/wp-admin/admin.php?page=top-solution', { waitUntil: 'load' });
  ok(r1.status() === 200 && await mp.locator('table.ts-status').count() === 1, 'shop manager: can open the dashboard');
  ok(await mp.locator('#adminmenu a[href*="top-solution-settings"]').count() === 0, 'shop manager: settings item is NOT in the menu');
  const r2 = await mp.goto(B + '/wp-admin/admin.php?page=top-solution-settings', { waitUntil: 'load' });
  ok(r2.status() === 403 && await mp.locator('table.ts-status, .ts-form').count() === 0, `shop manager: settings page refused (HTTP ${r2.status()})`);

  // ---------------- subscriber
  const s = await b.newContext({ viewport: { width: 1280, height: 900 } });
  const sp = await login(s, 'sub', 'sub-pass');
  for (const page of ['top-solution', 'top-solution-settings']) {
    const r = await sp.goto(B + '/wp-admin/admin.php?page=' + page, { waitUntil: 'load' });
    ok(r.status() === 403 || r.url().includes('profile.php') || r.url().includes('wp-login'), `subscriber: ${page} not accessible (HTTP ${r.status()})`);
    ok(await sp.locator('table.ts-status, .ts-form').count() === 0, `subscriber: ${page} shows no plugin content`);
  }

  // ---------------- phone width
  const ph = await b.newContext({ viewport: { width: 390, height: 844 } });
  const pp = await login(ph, 'admin', 'admin-pass');
  await pp.goto(B + '/wp-admin/admin.php?page=top-solution', { waitUntil: 'load' });
  const ov = await pp.evaluate(() => document.documentElement.scrollWidth - innerWidth);
  ok(ov <= 0, `phone width: dashboard has no horizontal overflow (${ov}px)`);
  await pp.screenshot({ path: OUT + '/plugin-dashboard-m.png', fullPage: true });

  await b.close();
  console.log(failed ? `\n${failed} FAILED` : '\nALL PASSED');
  process.exit(failed ? 1 : 0);
})();
