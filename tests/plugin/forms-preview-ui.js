// Front-end behaviour of the order form (shown as a preview in the builder). Needs fictional geography with communes + offices.
const { chromium } = require('playwright'); const B = 'http://127.0.0.1:8080';
let failed = 0; const ok = (c, l) => { console.log((c ? 'PASS ' : 'FAIL ') + l); if (!c) failed++; };
(async () => {
  const b = await chromium.launch(); const c = await b.newContext({ viewport: { width: 375, height: 800 }, locale: 'ar' }); const p = await c.newPage();
  await p.goto(B + '/wp-login.php', { waitUntil: 'load' }); await p.fill('#user_login', 'admin'); await p.fill('#user_pass', 'admin-pass');
  await Promise.all([p.waitForNavigation({ waitUntil: 'load' }), p.click('#wp-submit')]);
  await p.goto(B + '/wp-admin/admin.php?page=deco-store-forms', { waitUntil: 'load' });
  await Promise.all([p.waitForNavigation({ waitUntil: 'load' }), p.locator('table.widefat tbody tr').first().locator('th a').click()]);
  const f = p.locator('.ds-form-preview form');
  const opts = await f.locator('select[data-ds-role=wilaya] option, select[name*=wilaya] option').allTextContents();
  ok(opts.length >= 2, 'wilaya options present (' + opts.length + ')');
  ok(await f.locator('[data-ds-field=pickup_point], .ds-field--pickup_point').first().isHidden(), 'pickup hidden before choosing');
  ok(await f.locator('[name*=address]').first().isHidden(), 'address hidden before choosing');
  await f.locator('input[type=radio]').first().check();
  await p.waitForTimeout(200);
  ok(await f.locator('[name*=address]').first().isVisible(), 'home delivery shows address');
  await f.locator('input[type=radio]').nth(1).check(); await p.waitForTimeout(200);
  ok(await f.locator('[name*=address]').first().isHidden(), 'office delivery hides address');
  const w = f.locator('select[name*=wilaya]').first(); const val = await w.locator('option').nth(1).getAttribute('value'); await w.selectOption(val);
  await p.waitForTimeout(800);
  ok(await f.locator('select[name*=commune]').first().isEnabled(), 'commune enabled after wilaya (loaded over REST)');
  // empty submit shows summary and does not send
  await f.locator('button[type=submit]').click(); await p.waitForTimeout(300);
  ok(await f.locator('[role=alert], .ds-form__errors').first().isVisible(), 'validation summary shown');
  ok(await p.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), 'no overflow at 375px');
  await b.close(); console.log(failed ? 'FAILED ' + failed : 'ALL OK'); process.exit(failed ? 1 : 0);
})();
