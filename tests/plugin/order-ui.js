// Customer ordering flow in a real browser (guest, phone width). FICTIONAL data.
// Usage (from the folder with wordpress/ and order-setup.php): node order-ui.js <out-dir>
const { chromium } = require('playwright');
const { execFileSync } = require('child_process');
const fs = require('fs');
const path = require('path');
const OUT = process.argv[2] || '.';
const WP = process.env.DS_WP_DIR || process.cwd();
const php = (...a) => execFileSync('php', [path.join(WP, 'order-setup.php'), ...a], { cwd: WP }).toString();
let failed = 0; const ok = (c, l) => { console.log((c ? 'PASS ' : 'FAIL ') + l); if (!c) failed++; };
const axeSrc = fs.readFileSync(require.resolve('axe-core/axe.min.js'), 'utf8');

async function fill(p, phone) {
  await p.fill('input[name="full_name"], [data-maps-to="customer_name"] input', 'زبون واجهة تجريبي');
  await p.fill('[data-maps-to="phone"] input', phone);
  const wil = p.locator('[data-maps-to="wilaya"] select');
  await wil.selectOption({ label: 'ولاية تجريبية للواجهة' }).catch(async () => { const v = await wil.locator('option', { hasText: 'للواجهة' }).first().getAttribute('value'); await wil.selectOption(v); });
  const com = p.locator('[data-maps-to="commune"] select');
  await p.waitForFunction(() => { const s = document.querySelector('[data-maps-to="commune"] select'); return s && !s.disabled && s.options.length > 1; }, null, { timeout: 10000 });
  await com.selectOption({ index: 1 });
  await p.locator('[data-maps-to="delivery_method"] input[value="home"]').check();
  await p.fill('[data-maps-to="address"] textarea, [data-maps-to="address"] input', 'حي تجريبي');
  await p.waitForFunction(() => /500/.test(document.querySelector('.ds-order__summary').textContent), null, { timeout: 10000 });
}
async function axe(p) {
  await p.evaluate(axeSrc);
  return p.evaluate(async () => (await axe.run(document, { runOnly: ['wcag2a', 'wcag2aa'] })).violations.map(v => v.id + ' ' + v.nodes[0].target.join(' ')));
}

(async () => {
  const fx = JSON.parse(php('setup'));
  const b = await chromium.launch();
  const ctx = await b.newContext({ ...require('playwright').devices['Pixel 5'], locale: 'ar' });
  const p = await ctx.newPage();
  const errors = []; p.on('pageerror', e => errors.push(e.message));
  try {
    // 1. Page and live summary
    await p.goto(fx.url, { waitUntil: 'load' });
    ok(await p.locator('form[data-ds-order]').count() === 1, 'order form rendered on a page via shortcode');
    await p.waitForFunction(() => /2[\s٬,.]?500/.test(document.querySelector('.ds-order__summary').textContent), null, { timeout: 10000 });
    ok(true, 'summary shows the server price before any choice');
    ok(await p.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), 'no horizontal overflow on a phone');
    const a1 = await axe(p); ok(a1.length === 0, 'axe (form page): ' + (a1.join(', ') || 'none'));
    // 2. Empty submit -> client errors, nothing sent
    let posted = 0; p.on('request', r => { if (r.url().includes('/ds-core/v1/orders') && r.method() === 'POST') posted++; });
    await p.locator('.ds-submit').click();
    ok(await p.locator('.ds-errors').isVisible() && posted === 0, 'empty form: error summary, nothing sent');
    // 3. Fill; quantity 2 updates the total
    await fill(p, '0777000001');
    ok(await p.locator('.ds-errors').isHidden(), 'error list disappears once every field is corrected (' + (await p.locator('.ds-errors').innerText()).replace(/\s+/g, ' ') + ')');
    await p.fill('[name="ds_qty"]', '2'); await p.locator('[name="ds_qty"]').dispatchEvent('change');
    await p.waitForFunction(() => /5[\s٬,.]?500/.test(document.querySelector('.ds-order__total:last-child, .ds-order__lines').textContent), null, { timeout: 10000 });
    ok(true, 'total = 2 x 2500 + 500 delivery = 5500 (shown from the server)');
    const sentFields = await p.evaluate(() => Object.keys(window.DecoStoreForms.getValues(document.querySelector('form[data-ds-order]'))));
    ok(!sentFields.includes('null') && !sentFields.includes('ds_qty'), 'only form fields are sent as answers (' + sentFields.join(',') + ')');
    await p.screenshot({ path: OUT + '/order-form-phone.png', fullPage: true });
    await p.waitForTimeout(2200); // Past the 2-second bot check, so the next answer is about the total.
    // 4. Displayed total tampered once -> price_changed, then accepted on confirm
    let tampered = false;
    await p.route('**/ds-core/v1/orders', async route => {
      if (!tampered) { tampered = true; const body = JSON.parse(route.request().postData()); body.expected_total = '1'; return route.continue({ postData: JSON.stringify(body) }); }
      return route.continue();
    });
    await p.locator('.ds-submit').click();
    await p.waitForSelector('.ds-order__message:not([hidden])', { timeout: 10000 });
    const changedMsg = await p.locator('.ds-order__message').textContent();
    ok(/تغيّر المبلغ/.test(changedMsg), 'changed total: customer asked to confirm, no order (' + changedMsg + ')');
    ok(php('count', '0777000001') === '0', 'no order was created for the refused total');
    await p.unroute('**/ds-core/v1/orders');
    // 5. Lost response: server creates the order, browser sees a network error, retry returns the same order
    let dropped = false;
    await p.route('**/ds-core/v1/orders', async route => {
      if (!dropped) { dropped = true; await route.fetch(); return route.abort('failed'); }
      return route.continue();
    });
    await p.locator('.ds-submit').click();
    await p.waitForFunction(() => /الاتصال/.test(document.querySelector('.ds-order__message').textContent), null, { timeout: 10000 });
    ok(php('count', '0777000001') === '1', 'lost response: the order exists once on the server');
    // double click on retry
    await Promise.all([p.waitForURL(/ds_order=/, { timeout: 15000 }), p.locator('.ds-submit').dblclick()]);
    ok(php('count', '0777000001') === '1', 'retry + double click: still exactly one order');
    await p.unroute('**/ds-core/v1/orders');
    // 6. Confirmation page
    ok(await p.locator('h1').first().textContent().then(t => /تم استلام طلبك/.test(t)), 'confirmation page title');
    ok(/5[\s٬,.]?500/.test(await p.locator('.ds-confirmation__total').textContent()), 'confirmation shows the total payable on delivery');
    ok(await p.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), 'confirmation: no overflow on a phone');
    const robots = await p.locator('meta[name="robots"]').getAttribute('content').catch(() => '');
    ok(/noindex/.test(robots || ''), 'confirmation is noindex');
    const a2 = await axe(p); ok(a2.length === 0, 'axe (confirmation): ' + (a2.join(', ') || 'none'));
    await p.screenshot({ path: OUT + '/order-confirmation-phone.png', fullPage: true });
    const url = p.url();
    await p.reload({ waitUntil: 'load' });
    ok(php('count', '0777000001') === '1' && /تم استلام طلبك/.test(await p.locator('h1').first().textContent()), 'reloading the confirmation creates nothing');
    const bad = await p.goto(url.replace(/key=[^&]+/, 'key=wc_order_guess'), { waitUntil: 'load' });
    ok(bad.status() === 404, 'confirmation with a wrong key: 404');
    // 7. A very fast customer (< 2 s): the page waits and retries by itself
    await p.goto(fx.url, { waitUntil: 'load' });
    await fill(p, '0777000003');
    const t0 = Date.now();
    await Promise.all([p.waitForURL(/ds_order=/, { timeout: 20000 }), p.locator('.ds-submit').click()]);
    ok(php('count', '0777000003') === '1', 'fast customer: order accepted after an automatic retry (' + (Date.now() - t0) + ' ms)');
    // 8. Two parallel requests with the same key -> one order
    await p.goto(fx.url, { waitUntil: 'load' });
    await fill(p, '0777000002');
    await p.waitForTimeout(2200); // The form must be open at least 2 seconds (bot check).
    const results = await p.evaluate(async (productId) => {
      const form = document.querySelector('form[data-ds-order]');
      const rest = form.getAttribute('data-rest');
      const tok = await (await fetch(rest + 'token?product=' + productId + '&form=' + form.getAttribute('data-form-id'), { cache: 'no-store' })).json();
      await new Promise(r => setTimeout(r, 2100));
      const body = JSON.stringify({ token: tok.token, idempotency_key: 'parallel-test-key-000000000001' + Date.now(), product_id: productId, qty: 1, fields: window.DecoStoreForms.getValues(form) });
      const send = () => fetch(rest + 'orders', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body }).then(r => r.status);
      return Promise.all([send(), send(), send()]);
    }, fx.product);
    ok(results.filter(s => s === 201).length === 1 && php('count', '0777000002') === '1', 'three parallel identical submissions -> exactly one order (' + results.join(',') + ')');
    ok(errors.length === 0, 'no JavaScript errors: ' + (errors.join(' | ') || 'none'));
  } finally {
    await b.close();
    php('cleanup');
  }
  console.log(failed ? 'FAILED ' + failed : 'ALL OK'); process.exit(failed ? 1 : 0);
})();
