// WooCommerce order screens: Déco Store box and column. Usage: node order-admin-ui.js <out-dir>  (run from the folder with order-setup.php)
const { chromium } = require('playwright');
const { execFileSync } = require('child_process');
const path = require('path');
const OUT = process.argv[2] || '.'; const B = 'http://127.0.0.1:8080';
const WP = process.cwd();
const php = (...a) => execFileSync('php', [path.join(WP, 'order-setup.php'), ...a], { cwd: WP }).toString();
let failed = 0; const ok = (c, l) => { console.log((c ? 'PASS ' : 'FAIL ') + l); if (!c) failed++; };
(async () => {
  php('setup');
  const ids = JSON.parse(php('make-order'));
  const b = await chromium.launch(); const p = await b.newPage({ viewport: { width: 1280, height: 900 }, locale: 'ar' });
  try {
    ok(ids.every(Number.isInteger), 'two orders created: ' + ids.join(','));
    await p.goto(B + '/wp-login.php', { waitUntil: 'load' }); await p.fill('#user_login', 'admin'); await p.fill('#user_pass', 'admin-pass');
    await Promise.all([p.waitForNavigation({ waitUntil: 'load' }), p.click('#wp-submit')]);
    const hpos = await p.goto(B + '/wp-admin/admin.php?page=wc-orders', { waitUntil: 'load' }).then(r => !/post_type=shop_order/.test(p.url()) && r.status() === 200 && p.locator('table.wc-orders-list-table, table.wp-list-table').count());
    const listUrl = (await p.locator('#ds_source, th#ds_source, th.column-ds_source').count()) ? p.url() : B + '/wp-admin/edit.php?post_type=shop_order';
    if (!/page=wc-orders/.test(listUrl)) await p.goto(listUrl, { waitUntil: 'load' });
    ok(await p.locator('th.column-ds_source, th#ds_source').count() >= 1, 'orders list: Déco Store column');
    const row = p.locator(`tr:has(a[href*="${ids[1]}"])`).first();
    ok(/صفحة المنتج/.test(await row.innerText()) && /طلب مكرر محتمل/.test(await row.innerText()), 'orders list: source and duplicate flag shown');
    await p.screenshot({ path: OUT + '/orders-list.png' });
    const edit = /page=wc-orders/.test(p.url()) ? `${B}/wp-admin/admin.php?page=wc-orders&action=edit&id=${ids[1]}` : `${B}/wp-admin/post.php?post=${ids[1]}&action=edit`;
    await p.goto(edit, { waitUntil: 'load' });
    const box = p.locator('#ds-order-box');
    ok(await box.count() === 1, 'order screen: Déco Store box');
    const text = await box.innerText();
    ok(/التوصيل/.test(text) && /ولاية تجريبية للواجهة/.test(text) && /طلب مكرر محتمل/.test(text) && /محرك الأسعار v1/.test(text), 'box shows delivery, flag and versions');
    ok(!/ip_hash|_ds_|192\.0\.2/.test(await p.content()), 'no internal keys or network address on the order screen');
    await p.screenshot({ path: OUT + '/order-edit.png', fullPage: true });
  } finally {
    await b.close();
    php('cleanup');
  }
  console.log(failed ? 'FAILED ' + failed : 'ALL OK'); process.exit(failed ? 1 : 0);
})();
