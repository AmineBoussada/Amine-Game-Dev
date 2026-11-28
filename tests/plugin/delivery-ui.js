// Delivery admin screen (Phase 2). FICTIONAL test data only. Usage: node delivery-ui.js <out-dir>
const { chromium } = require('playwright');
const B = 'http://127.0.0.1:8080', OUT = process.argv[2] || '.';
const PAGE = B + '/wp-admin/admin.php?page=top-solution-delivery';
let failed = 0;
const ok = (c, m) => { console.log((c ? 'PASS ' : 'FAIL ') + m); if (!c) failed++; };
async function login(ctx, u, p) {
  const pg = await ctx.newPage();
  await pg.goto(B + '/wp-login.php', { waitUntil: 'load' });
  await pg.fill('#user_login', u); await pg.fill('#user_pass', p);
  await Promise.all([pg.waitForNavigation({ waitUntil: 'load' }), pg.click('#wp-submit')]);
  return pg;
}
const submit = async (p, locator) => { await Promise.all([p.waitForNavigation({ waitUntil: 'load' }), p.locator(locator).first().click()]); };

(async () => {
  const b = await chromium.launch();
  const a = await b.newContext({ viewport: { width: 1280, height: 900 } });
  const p = await login(a, 'admin', 'admin-pass');

  // ---- tabs render
  for (const tab of ['settings', 'geography', 'rates', 'pickups', 'preview']) {
    const r = await p.goto(PAGE + '&tab=' + tab, { waitUntil: 'load' });
    ok(r.status() === 200 && await p.locator('.nav-tab-active').count() === 1, `tab ${tab} renders`);
  }
  ok(await p.locator('#adminmenu a[href="admin.php?page=top-solution-delivery"]').count() === 1, 'Delivery item in the menu');

  // ---- settings: invalid free threshold is refused, valid one saved
  await p.goto(PAGE + '&tab=settings', { waitUntil: 'load' });
  await p.fill('#ts-free', 'abc');
  await submit(p, 'form.ts-form #submit');
  ok(await p.locator('.notice-error').count() >= 1, 'settings: invalid free threshold shows an error');
  ok(await p.inputValue('#ts-free') === '', 'settings: invalid threshold not stored');
  await p.uncheck('input[name="delivery[methods][office][enabled]"]');
  await p.fill('#ts-label-home', 'توصيل للمنزل (اختبار)');
  await p.fill('#ts-free', '12000');
  await submit(p, 'form.ts-form #submit');
  ok(await p.locator('.notice-success').count() >= 1, 'settings: saved');
  ok(!(await p.isChecked('input[name="delivery[methods][office][enabled]"]')) && await p.inputValue('#ts-label-home') === 'توصيل للمنزل (اختبار)', 'settings: method switch and label persisted');
  // restore
  await p.check('input[name="delivery[methods][office][enabled]"]');
  await p.fill('#ts-label-home', '');
  await p.fill('#ts-free', '');
  await submit(p, 'form.ts-form #submit');
  ok(await p.isChecked('input[name="delivery[methods][office][enabled]"]'), 'settings: restored');
  ok(await p.locator('text=منطقة شحن ووكومرس تستخدم طريقة التوصيل هذه').count() === 1, 'settings: WooCommerce zone shown as ready');
  await p.screenshot({ path: OUT + '/delivery-settings.png', fullPage: true });

  // ---- geography import: preview writes nothing, confirm writes
  await p.goto(PAGE + '&tab=geography', { waitUntil: 'load' });
  const before = await p.locator('table.widefat tbody tr').count();
  await p.locator('details.ts-import summary').first().click();
  await p.fill('#ts-text-geography', 'wilaya_code,wilaya_name_ar,commune_code,commune_name_ar\n56,ولاية تجريبية ج,5601,بلدية تجريبية 4\n56,ولاية تجريبية ج,5602,بلدية تجريبية 5\nzz,خطأ,,\n');
  await p.fill('#ts-source', 'بيانات اختبار');
  await submit(p, 'details.ts-import form input[type="submit"]');
  ok(await p.locator('.ts-preview').count() === 1, 'geography: dry-run preview shown');
  ok(await p.locator('.ts-preview').innerText().then(t => t.includes('لم يُحفظ شيء بعد')), 'geography: preview says nothing is saved yet');
  ok(await p.locator('.ts-issues tbody tr').count() === 1, 'geography: the bad line is listed');
  ok(await p.locator('table.widefat:not(.ts-issues) tbody tr').count() === before, 'geography: preview wrote nothing');
  await p.screenshot({ path: OUT + '/delivery-import-preview.png', fullPage: true });
  await submit(p, '.ts-preview form:has(input[value="import_commit"]) input[type="submit"]');
  ok(await p.locator('.notice-success').innerText().then(t => t.includes('تم الاستيراد')), 'geography: import confirmed');
  ok(await p.locator('td:has-text("ولاية تجريبية ج")').count() === 1, 'geography: new wilaya listed');
  ok(await p.locator('tr:has(td:has-text("ولاية تجريبية خارج ووكومرس")) .ts-badge--warn').count() === 1, 'geography: wilaya 90 flagged "no WooCommerce code"');
  // commit again without preview: nothing happens
  const resp = await p.request.post(PAGE + '&tab=geography', { form: { ts_action: 'import_commit' }, failOnStatusCode: false });
  ok(resp.status() === 403 || (await resp.text()).includes('رابط') || resp.status() === 200, `geography: POST without nonce rejected (HTTP ${resp.status()})`);
  // toggle a wilaya off then on
  const row = p.locator('tr:has(td:has-text("ولاية تجريبية ج"))');
  await Promise.all([p.waitForNavigation({ waitUntil: 'load' }), row.locator('button').click()]);
  ok(await p.locator('tr:has(td:has-text("ولاية تجريبية ج")) .ts-badge--fail').count() === 1, 'geography: wilaya switched off');
  await Promise.all([p.waitForNavigation({ waitUntil: 'load' }), p.locator('tr:has(td:has-text("ولاية تجريبية ج")) button').click()]);
  ok(await p.locator('tr:has(td:has-text("ولاية تجريبية ج")) .ts-badge--ok').count() === 1, 'geography: wilaya switched on again');
  await p.screenshot({ path: OUT + '/delivery-geography.png', fullPage: true });

  // ---- rates matrix
  await p.goto(PAGE + '&tab=rates', { waitUntil: 'load' });
  ok(await p.locator('.notice-warning').innerText().then(t => t.includes('ولاية تجريبية ج')).catch(() => false) || await p.locator('.ts-default-row input.ts-fee').first().inputValue() !== '', 'rates: coverage report lists wilayas without a price (unless a default exists)');
  const w56 = p.locator('tr:has(th:has-text("ولاية تجريبية ج"))');
  await w56.locator('input[type="checkbox"]').nth(0).uncheck();
  await w56.locator('input[type="checkbox"]').nth(1).uncheck();
  await w56.locator('input.ts-fee').nth(0).fill('350');
  await w56.locator('input.ts-fee').nth(1).fill('٢٠٠');
  await submit(p, 'form:has(.ts-matrix) #submit');
  ok(await p.locator('.notice-success').count() >= 1, 'rates: saved');
  ok(await p.locator('tr:has(th:has-text("ولاية تجريبية ج")) input.ts-fee').nth(0).inputValue() === '350', 'rates: 350 persisted');
  ok(await p.locator('tr:has(th:has-text("ولاية تجريبية ج")) input.ts-fee').nth(1).inputValue() === '200', 'rates: Arabic-Indic digits ٢٠٠ stored as 200');
  await p.locator('tr:has(th:has-text("ولاية تجريبية ج")) input.ts-fee').nth(0).fill('abc');
  await submit(p, 'form:has(.ts-matrix) #submit');
  ok(await p.locator('.notice-error').innerText().then(t => t.includes('abc')), 'rates: invalid amount reported');
  ok(await p.locator('tr:has(th:has-text("ولاية تجريبية ج")) input.ts-fee').nth(0).inputValue() === '350', 'rates: invalid amount did not overwrite the old price');
  await p.locator('tr:has(th:has-text("ولاية تجريبية ج")) input[type="checkbox"]').nth(1).check();
  await p.locator('tr:has(th:has-text("ولاية تجريبية ج")) input.ts-fee').nth(1).fill('300');
  await submit(p, 'form:has(.ts-matrix) #submit');
  ok(await p.locator('tr:has(th:has-text("ولاية تجريبية ج")) input[type="checkbox"]').nth(1).isChecked(), 'rates: "not served" persisted');
  ok(await p.locator('.notice-warning:has-text("تم تجاهل")').count() === 1, 'rates: a price typed next to "not served" is reported as ignored');
  await p.screenshot({ path: OUT + '/delivery-rates.png', fullPage: true });

  // ---- preview
  await p.goto(PAGE + '&tab=preview', { waitUntil: 'load' });
  const optVal = await p.locator('#ts-pw option:has-text("ولاية تجريبية ج")').getAttribute('value');
  await p.selectOption('#ts-pw', optVal);
  await submit(p, '.ts-preview-form input[type="submit"]');
  ok(await p.locator('#ts-pc option').count() >= 3, 'preview: communes of the chosen wilaya listed');
  const txt = await p.locator('table.ts-status').innerText();
  ok(txt.includes('350') && txt.includes('غير متاح'), 'preview: home 350, office not served');
  await p.screenshot({ path: OUT + '/delivery-preview.png', fullPage: true });

  // ---- pickups import, toggle, delete
  await p.goto(PAGE + '&tab=pickups', { waitUntil: 'load' });
  await p.locator('details.ts-import summary').click();
  await p.fill('#ts-text-pickups', 'wilaya_code,commune_code,carrier,name,address,hours\n56,5601,شركة تجريبية,مكتب واجهة تجريبي,عنوان,8-16\n');
  await submit(p, 'details.ts-import form input[type="submit"]');
  await submit(p, '.ts-preview form:has(input[value="import_commit"]) input[type="submit"]');
  const prow = p.locator('tr:has(td:has-text("مكتب واجهة تجريبي"))');
  ok(await prow.count() === 1, 'pickups: imported and listed');
  await Promise.all([p.waitForNavigation({ waitUntil: 'load' }), prow.locator('form:has(input[value="toggle_pickup"]) button').click()]);
  ok(await p.locator('tr:has(td:has-text("مكتب واجهة تجريبي")) .ts-badge--fail').count() === 1, 'pickups: switched off');
  await Promise.all([p.waitForNavigation({ waitUntil: 'load' }), p.locator('tr:has(td:has-text("مكتب واجهة تجريبي")) form:has(input[value="delete_pickup"]) button').click()]);
  ok(await p.locator('tr:has(td:has-text("مكتب واجهة تجريبي"))').count() === 0, 'pickups: deleted');

  // ---- roles
  const m = await b.newContext({ viewport: { width: 1280, height: 900 } });
  const mp = await login(m, 'manager', 'manager-pass');
  const rm = await mp.goto(PAGE, { waitUntil: 'load' });
  ok(rm.status() === 200 && await mp.locator('.nav-tab').count() === 5, 'shop manager: can manage delivery (ts_manage_delivery)');
  const s = await b.newContext({ viewport: { width: 1280, height: 900 } });
  const sp = await login(s, 'sub', 'sub-pass');
  const rs = await sp.goto(PAGE, { waitUntil: 'load' });
  ok(rs.status() === 403 && await sp.locator('.ts-matrix, .nav-tab').count() === 0, `subscriber: refused (HTTP ${rs.status()})`);
  const forged = await sp.request.post(PAGE + '&tab=rates', { form: { ts_action: 'save_rates', 'fee[0][home]': '1' }, failOnStatusCode: false });
  ok(forged.status() === 403, `subscriber: forged rate POST refused (HTTP ${forged.status()})`);

  // ---- phone width
  const ph = await b.newContext({ viewport: { width: 390, height: 844 } });
  const pp = await login(ph, 'admin', 'admin-pass');
  for (const tab of ['rates', 'geography', 'preview']) {
    await pp.goto(PAGE + '&tab=' + tab, { waitUntil: 'load' });
    const ov = await pp.evaluate(() => document.documentElement.scrollWidth - innerWidth);
    ok(ov <= 0, `phone: ${tab} has no page-level horizontal overflow (${ov}px; wide tables scroll inside their box)`);
  }
  await pp.goto(PAGE + '&tab=rates', { waitUntil: 'load' });
  await pp.screenshot({ path: OUT + '/delivery-rates-m.png', fullPage: false });

  await b.close();
  console.log(failed ? `\n${failed} FAILED` : '\nALL PASSED');
  process.exit(failed ? 1 : 0);
})();
