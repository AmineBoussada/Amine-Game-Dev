/* End-to-end interaction tests for the Tassili Immobilier frontend.
   Usage: npm test   (or: node tests/run.cjs)
   Starts a local static server, drives Chromium with Playwright, and prints one line per check. */
const http = require('http');
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

const ROOT = path.resolve(__dirname, '..');
const TYPES = { '.html': 'text/html; charset=utf-8', '.css': 'text/css', '.js': 'text/javascript', '.svg': 'image/svg+xml' };
const server = http.createServer((req, res) => {
  const url = decodeURIComponent(req.url.split('?')[0]);
  const file = path.join(ROOT, url === '/' ? 'index.html' : url);
  if (!file.startsWith(ROOT) || !fs.existsSync(file) || fs.statSync(file).isDirectory()) { res.writeHead(404); return res.end('not found'); }
  res.writeHead(200, { 'Content-Type': TYPES[path.extname(file)] || 'application/octet-stream' });
  fs.createReadStream(file).pipe(res);
});

let passed = 0, failed = 0;
const failures = [];
function ok(cond, name, extra) {
  if (cond) { passed++; console.log('  ✓ ' + name); }
  else { failed++; failures.push(name + (extra ? ' — ' + extra : '')); console.log('  ✗ ' + name + (extra ? ' — ' + extra : '')); }
}

(async () => {
  await new Promise(r => server.listen(0, r));
  const BASE = 'http://127.0.0.1:' + server.address().port + '/';
  const browser = await chromium.launch();
  const errors = [];

  async function newPage(opts) {
    const ctx = await browser.newContext(Object.assign({ viewport: { width: 1280, height: 900 } }, opts || {}));
    // Fonts come from Google; block them so tests run offline and fast.
    await ctx.route(/fonts\.(googleapis|gstatic)\.com/, r => r.abort());
    const page = await ctx.newPage();
    page.on('pageerror', e => errors.push(page.url() + ' :: ' + e.message));
    page.on('console', m => { if (m.type() === 'error' && !/Failed to load resource/.test(m.text())) errors.push(page.url() + ' :: ' + m.text()); });
    return page;
  }
  const go = (page, p) => page.goto(BASE + p, { waitUntil: 'load' });
  const missing = page => page.evaluate(() => TI.missing.slice());

  /* ---------------- Home ---------------- */
  console.log('Home');
  let page = await newPage();
  await go(page, 'index.html');
  ok(await page.locator('.site-header .nav a').count() === 6, 'header renders 6 nav links');
  ok(await page.locator('.site-footer .footer-grid').count() === 1, 'footer renders');
  ok(await page.locator('#featured-grid .card').count() === 6, '6 featured property cards');
  ok(await page.locator('#wilaya-grid .wilaya-tile').count() === 5, '5 wilaya tiles');
  ok(await page.locator('#type-grid .type-tile').count() === 5, '5 property-type tiles');
  ok(await page.locator('#new-grid .card').count() === 3, '3 new-development cards');
  ok(await page.locator('.wa-fab').getAttribute('href').then(h => h.startsWith('https://wa.me/213')), 'floating WhatsApp button links to wa.me');
  ok(await page.locator('#hero-bg').evaluate(i => i.complete && i.naturalWidth > 0), 'hero image renders');

  // Interactive search: count updates live, submit goes to the archive with the filters.
  const before = await page.locator('#hs-go-label').textContent();
  await page.click('.tab[data-tx="location"]');
  await page.selectOption('#hs-wilaya', '31');
  const after = await page.locator('#hs-go-label').textContent();
  ok(before !== after && /1/.test(after), 'search button count updates live', before + ' -> ' + after);
  ok(await page.locator('#hs-commune').isEnabled(), 'commune select enabled after choosing a wilaya');
  await page.click('#hs-go');
  await page.waitForURL(/biens\.html/);
  ok(/tx=location/.test(page.url()) && /wilaya=31/.test(page.url()), 'search submits to archive with filters', page.url());
  const cards = await page.locator('#results .card').count();
  ok(cards === 1, 'archive shows matching result for Louer + Oran', String(cards));
  ok(/Oran/.test(await page.locator('#a-title').textContent()), 'archive title names the wilaya');
  await page.context().close();

  // Budget formatting
  page = await newPage();
  await go(page, 'index.html');
  await page.fill('#hs-budget', '25000000');
  ok((await page.inputValue('#hs-budget')).replace(/\s| /g, '') === '25000000' && / /.test(await page.inputValue('#hs-budget')), 'budget input groups digits as you type');
  await page.context().close();

  /* ---------------- Archive ---------------- */
  console.log('Archive');
  page = await newPage();
  await go(page, 'biens.html');
  ok(await page.locator('#results .card').count() === 12, 'first page shows 12 of 16');
  ok(/16/.test(await page.locator('#a-count').textContent()), 'count shows 16 biens');
  ok(await page.locator('#pagination button[data-goto]').count() === 4, 'pagination: prev, 1, 2, next');
  await page.click('#pagination button[data-goto="2"]');
  ok(await page.locator('#results .card').count() === 4 && /page=2/.test(page.url()), 'page 2 shows remaining 4 and updates URL');

  await page.selectOption('#ff-type', 'villa');
  const types = await page.locator('#results .card').evaluateAll(cs => cs.map(c => TI.findProperty(c.dataset.ref).type));
  ok(types.length === 4 && types.every(t => t === 'villa'), 'type filter keeps only villas', JSON.stringify(types));
  ok(!/page=2/.test(page.url()) && /type=villa/.test(page.url()), 'filter change resets page and syncs URL');
  ok(await page.locator('.active-chip').count() === 1, 'active filter chip shown');

  await page.check('#filter-form input[name="amen"][value="vue_mer"]', { force: true });
  ok(await page.locator('#results .card').count() === 3, 'amenity chip narrows to sea-view villas');
  await page.click('.active-chip >> nth=0');
  ok(await page.locator('#ff-type').inputValue() === '', 'removing the type chip clears the select');

  await page.click('[data-reset] >> nth=0');
  await page.selectOption('#sort', 'price_asc');
  const prices = await page.locator('#results .card').evaluateAll(cs => cs.map(c => TI.findProperty(c.dataset.ref).price));
  ok(prices.every((p, i) => i === 0 || prices[i - 1] <= p), 'sort by price ascending', JSON.stringify(prices.slice(0, 5)));

  await page.click('.view-toggle [data-view="list"]');
  ok(await page.locator('#results.list').count() === 1 && /view=list/.test(page.url()), 'list view toggles and persists in URL');

  await page.fill('#ff-pmax', '1000');
  await page.waitForTimeout(450);
  ok(!(await page.locator('#empty').isHidden()) && await page.locator('#results .card').count() === 0, 'empty state when nothing matches');
  ok(await page.locator('#alert-btn').getAttribute('href').then(h => /wa\.me/.test(h)), 'WhatsApp alert link present');
  await page.click('#empty [data-reset]');
  ok(await page.locator('#results .card').count() === 12, 'reset from empty state restores results');

  // URL state restores on reload
  await go(page, 'biens.html?tx=vente&typo=F3&sort=price_desc');
  const f3 = await page.locator('#results .card').evaluateAll(cs => cs.map(c => TI.findProperty(c.dataset.ref)));
  ok(f3.length > 0 && f3.every(p => p.typo === 'F3' && p.tx === 'vente'), 'state restored from URL (vente + F3)');
  ok(await page.locator('#filter-form input[name="typo"][value="F3"]').isChecked(), 'F3 chip checked from URL');
  await page.context().close();

  /* ---------------- Favourites ---------------- */
  console.log('Favourites');
  page = await newPage();
  await go(page, 'index.html');
  await page.click('#featured-grid .card >> nth=0 >> .fav');
  ok(await page.locator('#featured-grid .card >> nth=0 >> .fav').getAttribute('aria-pressed') === 'true', 'heart toggles on');
  ok(await page.locator('[data-fav-count]').textContent() === '1' && await page.locator('[data-fav-count]').isVisible(), 'header favourites count = 1');
  ok(/favoris/i.test(await page.locator('#toast-region').textContent()), 'toast confirms');
  await go(page, 'biens.html?favoris=1');
  ok(await page.locator('#results .card').count() === 1, 'favourites page lists the saved property (persists across pages)');
  await page.click('#results .card .fav');
  ok(await page.locator('#results .card').count() === 0 && await page.locator('#empty').isVisible(), 'unsaving updates favourites list live');
  await page.context().close();

  /* ---------------- Property page ---------------- */
  console.log('Property');
  page = await newPage();
  await go(page, 'biens.html');
  await page.click('#results .card >> nth=0 >> .card-title a');
  await page.waitForURL(/bien\.html\?ref=/);
  ok(true, 'card title opens the property page');
  await go(page, 'bien.html?ref=TS-1024');
  ok(/Villa F6/.test(await page.locator('.p-head h1').textContent()), 'property title rendered');
  const total = await page.locator('[data-thumb]').count();
  ok(total === 7, 'villa gallery has 7 photos', String(total));
  ok((await page.locator('#g-counter').textContent()) === '1 / 7', 'counter starts at 1 / 7');
  await page.click('.g-next');
  ok((await page.locator('#g-counter').textContent()) === '2 / 7', 'next photo');
  await page.click('.g-prev'); await page.click('.g-prev');
  ok((await page.locator('#g-counter').textContent()) === '7 / 7', 'previous wraps to last');
  await page.click('[data-thumb="2"]');
  ok((await page.locator('#g-counter').textContent()) === '3 / 7' && await page.locator('[data-thumb="2"]').getAttribute('aria-current') === 'true', 'thumbnail selects photo');
  await page.click('#g-open');
  ok(await page.locator('#lightbox').evaluate(d => d.open), 'lightbox opens');
  await page.keyboard.press('ArrowRight');
  ok((await page.locator('#lb-counter').textContent()) === '4 / 7', 'ArrowRight advances in lightbox (LTR)');
  await page.click('.lb-next');
  ok((await page.locator('#lb-counter').textContent()) === '5 / 7', 'lightbox next button');
  await page.keyboard.press('Escape');
  ok(!(await page.locator('#lightbox').evaluate(d => d.open)), 'Escape closes lightbox');
  ok((await page.locator('#g-counter').textContent()) === '5 / 7', 'main gallery follows lightbox position');

  const wa = await page.locator('#agent-wa').getAttribute('href');
  ok(/wa\.me\/213555000001/.test(wa) && decodeURIComponent(wa).includes('TS-1024'), 'agent WhatsApp link carries the reference', wa.slice(0, 80));
  ok(await page.locator('.papers li.yes').count() === 4, 'papers list shows 4 available documents');
  ok(await page.locator('.p-main .p-sec').count() >= 4, 'description, papers, amenities, location sections');
  ok(await page.locator('.section-alt .card').count() === 3, '3 similar properties');

  await page.locator('.agent-card [data-visit]').click();
  ok(await page.locator('#visit-dialog').evaluate(d => d.open), 'visit dialog opens');
  await page.click('#visit-form [type="submit"]');
  ok(await page.locator('#v-name[aria-invalid="true"]').count() === 1 && await page.locator('#v-phone-err').isVisible(), 'empty visit form shows errors');
  await page.fill('#v-name', 'Lamia');
  await page.fill('#v-phone', '0612');
  await page.fill('#v-date', await page.evaluate(() => { const d = new Date(); d.setDate(d.getDate() - 1); return TI.forms.isoDate(d); }));
  await page.click('#visit-form [type="submit"]');
  ok(/numéro algérien/.test(await page.locator('#v-phone-err').textContent()), 'invalid phone message');
  ok(/demain/.test(await page.locator('#v-date-err').textContent()), 'past date rejected');
  const friday = await page.evaluate(() => { const d = TI.forms.tomorrow(); while (d.getDay() !== 5) d.setDate(d.getDate() + 1); return TI.forms.isoDate(d); });
  await page.fill('#v-date', friday);
  await page.click('#visit-form [type="submit"]');
  ok(/vendredi/.test(await page.locator('#v-date-err').textContent()), 'Friday rejected (agency closed)');
  const sunday = await page.evaluate(() => { const d = TI.forms.tomorrow(); while (d.getDay() !== 0) d.setDate(d.getDate() + 1); return TI.forms.isoDate(d); });
  await page.fill('#v-date', sunday);
  await page.fill('#v-phone', '0555 12 34 56');
  await page.click('#visit-form [type="submit"]');
  await page.waitForSelector('#visit-body .success-panel');
  ok(/Lamia/.test(await page.locator('#visit-body .success-panel').textContent()), 'valid visit request shows confirmation');
  await page.click('#visit-body [data-visit-close]');
  ok(!(await page.locator('#visit-dialog').evaluate(d => d.open)), 'visit dialog closes');

  await go(page, 'bien.html?ref=TS-9999');
  ok(await page.locator('.notfound h1').count() === 1, 'unknown reference shows not-found state');
  await page.context().close();

  /* ---------------- Language & direction ---------------- */
  console.log('Language / RTL');
  page = await newPage();
  await go(page, 'index.html');
  await page.click('[data-lang-toggle]');
  ok(await page.evaluate(() => document.documentElement.dir) === 'rtl' && await page.evaluate(() => document.documentElement.lang) === 'ar', 'switch sets lang=ar, dir=rtl');
  ok(/الرئيسية/.test(await page.locator('.nav').textContent()), 'nav translated to Arabic');
  ok(/[؀-ۿ]/.test(await page.locator('#featured-grid .card-title >> nth=0').textContent()), 'cards translated to Arabic');
  ok(/دج/.test(await page.locator('#featured-grid .price >> nth=0').textContent()), 'prices use دج');
  ok(/[؀-ۿ]/.test(await page.locator('#hs-go-label').textContent()), 'search panel re-rendered in Arabic');
  const nav = await page.locator('.nav a >> nth=0').boundingBox(), logoBox = await page.locator('.mainbar .logo').boundingBox();
  ok(logoBox.x > nav.x, 'layout mirrors: logo on the right in RTL');
  await go(page, 'biens.html');
  ok(await page.evaluate(() => document.documentElement.dir) === 'rtl', 'language persists to next page');
  await go(page, 'bien.html?ref=TS-1024');
  await page.click('#g-open');
  await page.keyboard.press('ArrowLeft');
  ok((await page.locator('#lb-counter').textContent()) === '2 / 7', 'ArrowLeft advances in RTL lightbox');
  await page.keyboard.press('Escape');
  ok(/[؀-ۿ]/.test(await page.locator('.p-head h1').textContent()), 'property page in Arabic');
  await page.locator('.agent-card [data-visit]').click();
  await page.click('#visit-form [type="submit"]');
  ok(/إجباري/.test(await page.locator('#v-name-err').textContent()), 'validation messages in Arabic');
  await page.keyboard.press('Escape');
  await go(page, 'index.html?lang=fr');
  ok(await page.evaluate(() => document.documentElement.dir) === 'ltr', '?lang=fr switches back');
  await page.context().close();

  /* ---------------- Theme ---------------- */
  console.log('Theme');
  page = await newPage({ colorScheme: 'light' });
  await go(page, 'index.html');
  const bg1 = await page.evaluate(() => getComputedStyle(document.body).backgroundColor);
  await page.click('[data-theme-toggle]');
  const bg2 = await page.evaluate(() => getComputedStyle(document.body).backgroundColor);
  ok(await page.evaluate(() => document.documentElement.dataset.theme) === 'dark' && bg1 !== bg2, 'theme toggle switches to dark', bg1 + ' -> ' + bg2);
  await go(page, 'contact.html');
  ok(await page.evaluate(() => document.documentElement.dataset.theme) === 'dark', 'theme persists across pages');
  await page.context().close();

  /* ---------------- Contact ---------------- */
  console.log('Contact');
  page = await newPage();
  await go(page, 'contact.html?sujet=vendre');
  ok(await page.inputValue('#c-subject') === 'sell', '?sujet=vendre preselects subject');
  await page.click('#contact-form [type="submit"]');
  ok(await page.locator('[data-form-summary]').isVisible(), 'error summary on empty submit');
  ok(await page.locator('#contact-form [aria-invalid="true"]').count() === 4, '4 required fields flagged (name, phone, message, consent)');
  ok(await page.evaluate(() => document.activeElement.id) === 'c-name', 'focus moves to first invalid field');
  await page.fill('#c-name', 'Karim B.');
  ok(await page.locator('#c-name[aria-invalid]').count() === 0, 'error clears live once fixed');
  await page.fill('#c-phone', '+213 661 23 45 67');
  await page.fill('#c-email', 'pas-un-email');
  await page.fill('#c-message', 'Je cherche un F4 à Chéraga, budget 30 millions.');
  await page.check('#c-consent');
  await page.click('#contact-form [type="submit"]');
  ok(await page.locator('#c-email[aria-invalid="true"]').count() === 1, 'invalid optional e-mail rejected');
  await page.fill('#c-email', 'karim@exemple.dz');
  await page.click('#contact-form [type="submit"]');
  await page.waitForSelector('#contact-success:not([hidden])');
  ok(/Karim B\./.test(await page.locator('#success-text').textContent()), 'success panel after valid submit');
  await page.click('#success-again');
  ok(await page.locator('#contact-form-wrap').isVisible() && (await page.inputValue('#c-name')) === '', 'send another message resets the form');
  await page.click('.faq details >> nth=0 >> summary');
  ok(await page.locator('.faq details >> nth=0').evaluate(d => d.open), 'FAQ item expands');
  await page.context().close();

  /* ---------------- Mobile ---------------- */
  console.log('Mobile (390px)');
  page = await newPage({ viewport: { width: 390, height: 844 }, hasTouch: true, isMobile: true });
  await go(page, 'index.html');
  ok(await page.locator('.nav').isHidden() && await page.locator('.burger').isVisible(), 'desktop nav hidden, burger shown');
  await page.click('.burger');
  ok(await page.locator('#drawer').isVisible() && await page.locator('.burger').getAttribute('aria-expanded') === 'true', 'drawer opens');
  await page.keyboard.press('Escape');
  ok(await page.locator('#drawer').isHidden(), 'Escape closes drawer');
  await page.click('.burger');
  await page.click('.drawer-backdrop', { position: { x: 380, y: 400 } });
  ok(await page.locator('#drawer').isHidden(), 'backdrop tap closes drawer');
  ok(await page.locator('#hs-budget').isHidden(), 'search extras collapsed on phone');
  await page.click('#hs-more');
  ok(await page.locator('#hs-budget').isVisible(), '"Plus de critères" reveals extra fields');
  await page.click('.burger');
  await page.click('.drawer-nav a[href="contact.html"]');
  await page.waitForURL(/contact\.html/);
  ok(true, 'drawer link navigates');

  await go(page, 'biens.html');
  ok(await page.locator('#filters').isHidden() && await page.locator('#filter-open').isVisible(), 'filters hidden behind button on phone');
  await page.click('#filter-open');
  ok(await page.locator('#filters').isVisible(), 'filter sheet opens');
  await page.locator('#filter-form .seg label', { hasText: 'Louer' }).click();
  ok(/Voir 5 biens/.test(await page.locator('#filters-apply').textContent()), 'apply button shows live count', await page.locator('#filters-apply').textContent());
  await page.click('#filters-apply');
  ok(await page.locator('#filters').isHidden() && /\(1\)/.test(await page.locator('#filter-open').textContent()), 'apply closes sheet, button shows 1 active filter');

  await go(page, 'bien.html?ref=TS-0871');
  ok(await page.locator('#m-bar').isVisible(), 'sticky contact bar on mobile property page');
  ok(await page.locator('.wa-fab').isHidden(), 'floating WA hidden where the bar exists');
  const box = await page.locator('#g-main').boundingBox();
  await page.mouse.move(box.x + box.width * .8, box.y + box.height / 2);
  await page.mouse.down(); await page.mouse.move(box.x + box.width * .2, box.y + box.height / 2, { steps: 5 }); await page.mouse.up();
  ok((await page.locator('#g-counter').textContent()) === '2 / 6', 'swipe left advances photo', await page.locator('#g-counter').textContent());
  ok(!(await page.locator('#lightbox').evaluate(d => d.open)), 'swipe does not open the lightbox');
  await page.context().close();

  /* ---------------- No horizontal overflow ---------------- */
  console.log('Layout: no horizontal scroll');
  const pages = ['index.html', 'biens.html', 'bien.html?ref=TS-1024', 'bien.html?ref=TS-0990', 'a-propos.html', 'contact.html'];
  for (const w of [360, 768, 1280]) {
    for (const lang of ['fr', 'ar']) {
      page = await newPage({ viewport: { width: w, height: 800 } });
      const bad = [];
      for (const p of pages) {
        await go(page, p + (p.includes('?') ? '&' : '?') + 'lang=' + lang);
        const over = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
        if (over > 0) bad.push(p + ' +' + over + 'px');
        const miss = await missing(page);
        if (miss.length) bad.push(p + ' missing ' + miss.join(','));
      }
      ok(!bad.length, w + 'px ' + lang.toUpperCase() + ': all pages fit, no missing strings', bad.join('; '));
      await page.context().close();
    }
  }

  ok(errors.length === 0, 'no JavaScript errors on any page', errors.slice(0, 5).join(' | '));

  await browser.close();
  server.close();
  console.log('\n' + passed + ' passed, ' + failed + ' failed');
  if (failed) { console.log('Failures:\n- ' + failures.join('\n- ')); process.exit(1); }
})().catch(e => { console.error(e); server.close(); process.exit(1); });
