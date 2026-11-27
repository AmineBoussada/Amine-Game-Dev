const { chromium } = require('playwright');
const fs = require('fs'); const B = 'http://127.0.0.1:8080'; const OUT = process.argv[2];
const axeSrc = fs.readFileSync(__dirname + '/node_modules/axe-core/axe.min.js', 'utf8');
(async () => {
  const b = await chromium.launch();
  for (const [vn, w, h] of [['m', 390, 844], ['d', 1280, 900]]) {
    const ctx = await b.newContext({ viewport: { width: w, height: h } });
    const p = await ctx.newPage();
    const errs = []; p.on('pageerror', e => errs.push(e.message));
    // find products via shop page
    await p.goto(B + '/shop/', { waitUntil: 'networkidle' });
    const links = await p.$$eval('ul.products li.product > a.woocommerce-LoopProduct-link', as => as.map(a => a.href));
    await p.goto(links[2], { waitUntil: 'networkidle' });               // simple
    await p.screenshot({ path: `${OUT}/w-single-${vn}.png`, fullPage: true });
    let ov = await p.evaluate(() => document.documentElement.scrollWidth - innerWidth);
    let axe = await (async()=>{await p.evaluate(axeSrc);return p.evaluate(async()=>(await axe.run(document,{runOnly:['wcag2a','wcag2aa','best-practice']})).violations.map(v=>v.id+'x'+v.nodes.length))})();
    console.log(vn, 'single overflow', ov, 'axe', axe.join(',') || 'none');
    await p.click('button.single_add_to_cart_button'); await p.waitForLoadState('networkidle');
    await p.goto(B + '/cart/', { waitUntil: 'networkidle' });
    await p.waitForSelector('.wc-block-cart, .woocommerce-cart-form', { timeout: 10000 }); await p.waitForFunction(()=>!document.querySelector('.wc-block-components-skeleton__element, .wc-block-components-loading-mask'),null,{timeout:30000}).catch(()=>console.log('skeleton timeout')); await p.waitForTimeout(500);
    await p.screenshot({ path: `${OUT}/w-cart-${vn}.png`, fullPage: true });
    ov = await p.evaluate(() => document.documentElement.scrollWidth - innerWidth);
    console.log(vn, 'cart overflow', ov);
    await p.goto(B + '/checkout/', { waitUntil: 'networkidle' });
    await p.waitForFunction(()=>!document.querySelector('.wc-block-components-skeleton__element, .wc-block-components-loading-mask'),null,{timeout:30000}).catch(()=>console.log('skeleton timeout')); await p.waitForTimeout(500);
    await p.screenshot({ path: `${OUT}/w-checkout-${vn}.png`, fullPage: true });
    ov = await p.evaluate(() => document.documentElement.scrollWidth - innerWidth);
    console.log(vn, 'checkout overflow', ov, 'cod-visible', await p.locator('text=الدفع عند الاستلام').count());
    // variable product
    await p.goto(B + '/shop/', { waitUntil: 'networkidle' });
    const vlink = await p.$$eval('ul.products li.product', lis => { const li = lis.find(l => /–/.test(l.innerText)); return li && li.querySelector('a').href; });
    if (vlink) { await p.goto(vlink, { waitUntil: 'networkidle' }); await p.screenshot({ path: `${OUT}/w-variable-${vn}.png`, fullPage: true }); console.log(vn, 'variable overflow', await p.evaluate(() => document.documentElement.scrollWidth - innerWidth)); }
    if (errs.length) console.log('pageerrors', errs);
    await ctx.close();
  }
  await b.close();
})();
