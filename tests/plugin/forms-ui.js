// Form builder browser checks. Usage: node forms-ui.js <out-dir>   (needs the test site + users from users-setup.php)
const { chromium } = require('playwright');
const OUT = process.argv[2] || '.'; const B = 'http://127.0.0.1:8080';
let failed = 0; const ok = (c, l) => { console.log((c ? 'PASS ' : 'FAIL ') + l); if (!c) failed++; };
async function login(b, u, pw) {
  const c = await b.newContext({ viewport: { width: 1280, height: 900 }, locale: 'ar' }); const p = await c.newPage();
  await p.goto(B + '/wp-login.php', { waitUntil: 'load' }); await p.fill('#user_login', u); await p.fill('#user_pass', pw);
  await Promise.all([p.waitForNavigation({ waitUntil: 'load' }), p.click('#wp-submit')]); return p;
}
(async () => {
  const b = await chromium.launch(); const p = await login(b, 'admin', 'admin-pass');
  const go = (q) => p.goto(B + '/wp-admin/admin.php?page=deco-store-forms' + (q || ''), { waitUntil: 'load' });
  await go();
  ok(await p.locator('table.widefat tbody tr').count() >= 1, 'list shows forms');
  const name = 'نموذج اختبار ' + Date.now();
  await p.fill('#ds-new-name', name);
  await Promise.all([p.waitForNavigation({ waitUntil: 'load' }), p.click('.ds-inline-form input[type=submit]')]);
  ok(/form=\d+/.test(p.url()), 'create redirects to builder');
  ok(await p.locator('.ds-b-item').count() === 8, 'builder shows 8 fields');
  ok(await p.locator('.ds-b-item [data-act=del]').count() === 4, 'core fields have no delete button (4 of 8 deletable)');
  // add a text field, give it a label, move it up
  await p.selectOption('#ds-add-type', 'text'); await p.click('#ds-add-field');
  ok(await p.locator('.ds-b-item').count() === 9, 'added field');
  await p.fill('#ds-f8-label', 'اسم المهندس'); await p.fill('#ds-f8-key', 'architect');
  await p.click('.ds-b-item:last-child [data-act=up]');
  ok(await p.locator('.ds-b-item').nth(7).getAttribute('data-key') === 'architect', 'moved up');
  // conditional rule on the new field
  await p.click('.ds-b-item[data-key=architect] [data-act=edit]');
  await p.selectOption('[id^=ds-vis-]', 'conditional');
  ok(await p.locator('.ds-b-rule').count() === 1, 'rule row added');
  await p.selectOption('.ds-b-rule select >> nth=0', 'delivery_method'); await p.fill('.ds-b-rule input', 'home');
  // save
  await Promise.all([p.waitForNavigation({ waitUntil: 'load' }), p.click('#submit')]);
  ok(await p.locator('.notice-success').count() === 1, 'saved with success notice');
  ok(await p.locator('.ds-b-item').count() === 9, 'reload keeps 9 fields');
  // invalid: remove the label of the architect field -> server refuses, draft kept
  await p.click('.ds-b-item[data-key=architect] [data-act=edit]');
  await p.fill('#ds-f7-label', '');
  await Promise.all([p.waitForNavigation({ waitUntil: 'load' }), p.click('#submit')]);
  ok(await p.locator('.notice-error').count() === 1, 'invalid form refused with error');
  ok(await p.locator('.ds-b-item').count() === 9, 'draft kept after refusal');
  // phone-width layout, no horizontal overflow
  await p.setViewportSize({ width: 375, height: 800 });
  ok(await p.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1), 'no horizontal overflow at 375px');
  await p.screenshot({ path: OUT + '/forms-builder-mobile.png', fullPage: true });
  await p.setViewportSize({ width: 1280, height: 900 });
  // preview: conditional pickup/address fields
  const m = p.locator('.ds-form-preview form'); ok(await m.count() === 1, 'preview rendered');
  await p.screenshot({ path: OUT + '/forms-builder.png', fullPage: true });
  // roles
  const sub = await login(b, 'sub', 'sub-pass'); const r = await sub.goto(B + '/wp-admin/admin.php?page=deco-store-forms', { waitUntil: 'load' });
  ok(r.status() === 403 || /ليست لديك صلاحية|not allowed|عذرًا/.test(await sub.content()), 'subscriber blocked');
  // CSRF: POST without nonce is refused
  const res = await p.request.post(B + '/wp-admin/admin.php?page=deco-store-forms', { form: { ds_action: 'delete_form', form_id: '1' } });
  ok(res.status() === 403 || /rest_cookie|link you followed has expired|انتهت/.test(await res.text()), 'POST without nonce refused');
  await b.close(); console.log(failed ? 'FAILED ' + failed : 'ALL OK'); process.exit(failed ? 1 : 0);
})();
