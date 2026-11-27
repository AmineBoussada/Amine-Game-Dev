const { chromium } = require('playwright');
const fs = require('fs');
const BASE = 'http://127.0.0.1:8080';
const OUT = process.argv[2];
const axeSrc = fs.readFileSync(__dirname + '/node_modules/axe-core/axe.min.js', 'utf8');
const pages = [
  ['home', '/'], ['shop', '/shop/'], ['categories', '/categories/'],
  ['category', '/product-category/%D8%A7%D9%84%D9%85%D8%B7%D8%A7%D8%A8%D8%AE/'],
  ['about', '/about/'], ['contact', '/contact/'], ['cart', '/cart/'],
  ['search', '/?s=%D9%85%D9%86%D8%AA%D8%AC&post_type=product'], ['noresults', '/?s=zzz'], ['404', '/nonexistent-page/'],
  ['account', '/my-account/'], ['blog', '/?post_type=post&s=%D9%85%D9%82%D8%A7%D9%84%D8%A9'],
];
const viewports = [['m', 390, 844], ['t', 768, 1024], ['d', 1280, 900]];
const results = [];
(async () => {
  const browser = await chromium.launch();
  for (const [vn, w, h] of viewports) {
    const ctx = await browser.newContext({ viewport: { width: w, height: h }, deviceScaleFactor: 1 });
    for (const [name, url] of pages) {
      const page = await ctx.newPage();
      const errors = [], failed = [];
      page.on('console', m => { if (m.type() === 'error') errors.push(m.text().slice(0, 200)); });
      page.on('pageerror', e => errors.push('pageerror: ' + e.message.slice(0, 200)));
      page.on('requestfailed', r => failed.push(r.url().slice(0, 120)));
      const resp = await page.goto(BASE + url, { waitUntil: 'networkidle' });
      await page.evaluate(() => document.fonts.ready);
      const info = await page.evaluate(() => ({
        overflow: document.documentElement.scrollWidth - innerWidth,
        h1: document.querySelectorAll('h1').length,
        dir: document.documentElement.dir,
        lang: document.documentElement.lang,
        font: getComputedStyle(document.body).fontFamily.split(',')[0],
        fontsLoaded: [...document.fonts].filter(f => f.status === 'loaded').map(f => f.family + ' ' + f.weight).join('|'),
        mains: document.querySelectorAll('main').length,
        header: !!document.querySelector('.ts-header'),
        footer: !!document.querySelector('.ts-footer'),
      }));
      let axe = null;
      if (vn === 'm' || vn === 'd') {
        await page.evaluate(axeSrc);
        axe = await page.evaluate(async () => {
          const r = await axe.run(document, { runOnly: ['wcag2a', 'wcag2aa', 'wcag21aa', 'best-practice'] });
          return r.violations.map(v => ({ id: v.id, impact: v.impact, n: v.nodes.length, ex: v.nodes[0].target.join(' ').slice(0, 90) }));
        });
      }
      await page.screenshot({ path: `${OUT}/s-${name}-${vn}.png`, fullPage: true });
      results.push({ vn, name, status: resp.status(), errors, failed, ...info, axe });
      await page.close();
    }
    await ctx.close();
  }
  await browser.close();
  fs.writeFileSync(OUT + '/results.json', JSON.stringify(results, null, 1));
  for (const r of results) {
    const flags = [];
    if (r.overflow > 0) flags.push('OVERFLOW ' + r.overflow);
    if (r.h1 !== 1 && r.name !== 'cart') flags.push('h1=' + r.h1);
    if (r.mains !== 1) flags.push('mains=' + r.mains);
    if (r.dir !== 'rtl') flags.push('dir=' + r.dir);
    if (r.errors.length) flags.push('console:' + r.errors.length);
    if (r.failed.length) flags.push('failed:' + r.failed.length);
    if (r.axe && r.axe.length) flags.push('axe:' + r.axe.map(a => a.id + '(' + a.impact + ')x' + a.n).join(','));
    console.log(r.vn, r.name.padEnd(10), r.status, flags.join(' ') || 'ok');
  }
})();
