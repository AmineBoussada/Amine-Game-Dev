// Offers admin browser checks. Usage: node offers-ui.js <product-id> <out-dir>  (needs the test site + users from users-setup.php)
const { chromium } = require('playwright');
const fs = require('fs');
const PID = process.argv[2]; const OUT = process.argv[3] || '.'; const B = 'http://127.0.0.1:8080';
let failed = 0; const ok = (c, l) => { console.log((c ? 'PASS ' : 'FAIL ') + l); if (!c) failed++; };
async function login(b, u, pw) {
  const c = await b.newContext({ viewport: { width: 1280, height: 900 }, locale: 'ar' }); const p = await c.newPage();
  await p.goto(B + '/wp-login.php', { waitUntil: 'load' }); await p.fill('#user_login', u); await p.fill('#user_pass', pw);
  await Promise.all([p.waitForNavigation({ waitUntil: 'load' }), p.click('#wp-submit')]); return p;
}
(async () => {
  const b = await chromium.launch(); const p = await login(b, 'admin', 'admin-pass');
  const page = (q) => p.goto(B + '/wp-admin/admin.php?page=top-solution-offers' + (q || ''), { waitUntil: 'load' });
  const save = () => Promise.all([p.waitForNavigation({ waitUntil: 'load' }), p.click('#submit')]);
  await page();
  ok(await p.locator('.ts-empty, table.widefat').count() === 1, 'list page renders');
  // 1. create a quantity discount
  await page('&offer=new');
  await p.fill('#ts-o-label', 'خصم الكمية تجريبي'); await p.selectOption('#ts-o-type', 'qty_discount'); await p.selectOption('#ts-o-status', 'active');
  ok(await p.locator('fieldset[data-types="bundle"]').isHidden(), 'bundle section hidden for qty type');
  await p.fill('#ts-o-tiers', '2|percent|10\n3|percent|20'); await p.fill('#ts-o-priority', '1');
  await save();
  ok(await p.locator('.notice-success').count() >= 1, 'qty offer saved');
  ok(await p.locator('table.widefat tbody tr').count() === 1, 'listed');
  // 2. invalid refused, typed values kept
  await page('&offer=new');
  await p.fill('#ts-o-label', 'سيء'); await p.selectOption('#ts-o-type', 'qty_discount'); await p.fill('#ts-o-tiers', '2|percent|abc');
  await save();
  ok(await p.locator('.notice-error').count() === 1, 'invalid offer refused with an error');
  await page('&offer=new');
  ok(await p.inputValue('#ts-o-tiers') === '2|percent|abc', 'draft kept after refusal');
  await page();
  ok(await p.locator('table.widefat tbody tr').count() === 1, 'invalid offer was not stored');
  // 3. conflicting second offer (same priority, all products) -> warning
  await page('&offer=new');
  await p.fill('#ts-o-label', 'قاعدة نسبة'); await p.selectOption('#ts-o-type', 'custom_rule'); await p.selectOption('#ts-o-action', 'percent_off'); await p.fill('#ts-o-action-value', '5'); await p.selectOption('#ts-o-status', 'active'); await p.fill('#ts-o-priority', '1');
  await save();
  ok(await p.locator('.notice-warning').count() === 1, 'warning about equal priority overlap');
  ok(await p.locator('.ts-badge--warn:has-text("تعارض")').count() === 2, 'both offers flagged in the list');
  // 4. free gift + selectable + exclusive flags
  await page('&offer=new');
  await p.fill('#ts-o-label', 'هدية'); await p.selectOption('#ts-o-type', 'free_gift'); await p.selectOption('#ts-o-status', 'active'); await p.fill('#ts-o-gift-product', PID); await p.check('input[name=selectable]'); await p.check('input[name=exclusive]');
  await save();
  ok(await p.locator('.ts-badge:has-text("يختاره العميل")').count() === 1 && await p.locator('.ts-badge:has-text("حصري")').count() === 1, 'selectable and exclusive badges');
  // 5. edit round trip keeps values
  await p.locator('table.widefat tbody tr').first().locator('th a').click(); await p.waitForLoadState('load');
  ok((await p.inputValue('#ts-o-label')) === 'هدية' && (await p.inputValue('#ts-o-gift-product')) === PID, 'editor reloads saved values');
  // 6. status toggle and duplicate
  await page();
  const rows = await p.locator('table.widefat tbody tr').count();
  await Promise.all([p.waitForNavigation({ waitUntil: 'load' }), p.locator('table.widefat tbody tr').first().locator('button:has-text("نسخ")').click()]);
  ok(await p.locator('table.widefat tbody tr').count() === rows + 1, 'duplicate adds a row');
  await Promise.all([p.waitForNavigation({ waitUntil: 'load' }), p.locator('table.widefat tbody tr').first().locator('button:has-text("حذف")').click()]);
  ok(await p.locator('table.widefat tbody tr').count() === rows, 'delete removes it');
  // 7. simulator
  await page('&tab=simulator&product=' + PID + '&qty=2');
  ok(await p.locator('.ts-sim-result').count() === 1, 'simulator shows a result');
  const txt = await p.locator('.ts-sim-result').innerText();
  ok(/1[\s٬,.]?800/.test(txt) && !/1[\s٬,.]?710/.test(txt), 'simulator applies the bigger discount (10 % beats 5 %): 1800');
  ok(/خصم الكمية تجريبي/.test(await p.locator('.ts-sim-list').first().innerText()), 'the winning offer is named');
  ok(await p.locator('.ts-sim-list li').count() >= 1, 'simulator explains applied/rejected offers');
  const rej = await p.locator('.ts-sim-list').last().innerText();
  ok(/لم يخترْه/.test(rej) && /أعلى أولوية/.test(rej), 'rejected list explains: gift card not chosen, rule outranked');
  await p.screenshot({ path: OUT + '/offers-simulator.png', fullPage: true });
  await page('&tab=simulator&product=999999');
  ok(await p.locator('.notice-error').count() === 1, 'simulator refuses an unknown product');
  // 8. mobile
  await p.setViewportSize({ width: 375, height: 800 }); await page('&offer=new');
  ok(await p.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), 'editor: no horizontal overflow at 375px');
  await page();
  ok(await p.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), 'list: no horizontal overflow at 375px');
  await p.setViewportSize({ width: 1280, height: 900 }); await page(); await p.screenshot({ path: OUT + '/offers-list.png', fullPage: true });
  // 9. security
  const sub = await login(b, 'sub', 'sub-pass'); const r = await sub.goto(B + '/wp-admin/admin.php?page=top-solution-offers', { waitUntil: 'load' });
  ok(r.status() === 403 || /ليست لديك صلاحية|not allowed|عذرًا/.test(await sub.content()), 'subscriber blocked');
  const res = await p.request.post(B + '/wp-admin/admin.php?page=top-solution-offers', { form: { ts_action: 'delete_offer', offer_id: '1' } });
  ok(res.status() === 403 || /expired|انتهت/.test(await res.text()), 'POST without nonce refused');
  // 10. settings tab: values saved, out-of-range falls back to the default, other tabs untouched
  const settings = (t) => p.goto(B + '/wp-admin/admin.php?page=top-solution-settings&tab=' + t, { waitUntil: 'load' });
  await settings('offers');
  await p.fill('#ts-max-gifts', '3'); await p.fill('#ts-min-price', '40'); await save();
  await settings('offers');
  ok((await p.inputValue('#ts-max-gifts')) === '3' && (await p.inputValue('#ts-min-price')) === '40', 'offer settings saved');
  await p.evaluate(() => { document.querySelector('#ts-min-price').type = 'text'; }); await p.fill('#ts-min-price', '500'); await save();
  await settings('offers');
  ok((await p.inputValue('#ts-min-price')) === '0' && (await p.inputValue('#ts-max-gifts')) === '3', 'out-of-range value falls back to the default, other value kept');
  await p.fill('#ts-max-gifts', '1'); await p.fill('#ts-min-price', '0'); await save();
  // cleanup: delete all offers
  await page();
  while (await p.locator('table.widefat tbody tr').count()) { await Promise.all([p.waitForNavigation({ waitUntil: 'load' }), p.locator('table.widefat tbody tr').first().locator('button:has-text("حذف")').click()]); }
  await b.close(); console.log(failed ? 'FAILED ' + failed : 'ALL OK'); process.exit(failed ? 1 : 0);
})();
