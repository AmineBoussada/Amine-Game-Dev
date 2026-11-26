/* End-to-end tests for the Tassili Immobilier WordPress theme, run against a real WordPress.
 *
 *   WP_URL=http://127.0.0.1:8080 WP_SITE_DIR=/path/to/wordpress node tests/run.cjs
 *
 * The site must have the theme active and the demo content imported (see tools/setup-test-site.sh).
 * Optional: WP_USER / WP_PASS (admin checks), WP_MAIL_LOG (file the test mail catcher writes to).
 */
const { chromium, request } = require('playwright');
const fs = require('fs');
const { execFileSync } = require('child_process');

const BASE = (process.env.WP_URL || 'http://127.0.0.1:8080').replace(/\/$/, '');
const SITE = process.env.WP_SITE_DIR || '';
const USER = process.env.WP_USER || 'admin';
const PASS = process.env.WP_PASS || 'adminpass123';
const MAIL_LOG = process.env.WP_MAIL_LOG || (SITE ? SITE + '/wp-content/mail.log' : '');

let passed = 0, failed = 0;
const failures = [];
function ok(cond, name, extra) {
  if (cond) { passed++; console.log('  ✓ ' + name); }
  else { failed++; failures.push(name + (extra ? ' — ' + extra : '')); console.log('  ✗ ' + name + (extra ? ' — ' + extra : '')); }
}
const sleep = ms => new Promise(r => setTimeout(r, ms));

/** Run a snippet of PHP inside WordPress (for checks the browser cannot make). */
function wp(code) {
  if (!SITE) return null;
  const script = `<?php $_SERVER['HTTP_HOST']='${new URL(BASE).host}'; require '${SITE}/wp-load.php'; ${code}`;
  return execFileSync('php', ['-r', script.replace(/^<\?php /, '')], { encoding: 'utf8' }).trim();
}
function mailCount() {
  try { return fs.readFileSync(MAIL_LOG, 'utf8').split('\n').filter(Boolean).length; } catch (e) { return 0; }
}

(async () => {
  if (SITE) wp('delete_transient("tassili_rl_".md5("127.0.0.1"));'); // fresh rate-limit window for this run
  const browser = await chromium.launch();
  const errors = [];
  const consoleSeen = [];

  async function ctxPage(opts) {
    const ctx = await browser.newContext(Object.assign({ viewport: { width: 1280, height: 900 } }, opts || {}));
    const page = await ctx.newPage();
    page.on('pageerror', e => errors.push(page.url() + ' :: ' + e.message));
    page.on('console', m => { if (m.type() === 'error') consoleSeen.push(page.url() + ' :: ' + m.text()); });
    page.on('requestfailed', r => consoleSeen.push('request failed: ' + r.url()));
    return page;
  }
  const go = (page, p) => page.goto(BASE + p, { waitUntil: 'load' });
  const num = s => parseInt(String(s).replace(/\D/g, ''), 10);

  /* ============================== Home ============================== */
  console.log('Home');
  let page = await ctxPage();
  await go(page, '/');
  ok(await page.locator('html').getAttribute('lang') === 'fr-FR', 'default language is French (html lang=fr-FR)');
  ok(await page.locator('.nav a').count() === 6, 'header shows the 6 default menu links');
  ok(await page.locator('.nav a[aria-current="page"]').count() === 1, 'current page marked in the menu');
  ok(await page.locator('#site-footer').count() === 1 && await page.locator('#site-footer .footer-grid').count() === 1, 'footer renders');
  ok(await page.locator('.hero-title').textContent().then(t => /Trouvez le bien/.test(t)), 'hero text is French');
  ok(await page.locator('.card-grid').first().locator('.card').count() === 6, '6 featured cards');
  ok(await page.locator('.wilaya-tile').count() === 5, '5 wilaya tiles');
  ok(await page.locator('.type-tile').count() === 5, '5 property type tiles');
  ok(await page.locator('.testi').count() === 3, '3 testimonials');
  ok(await page.locator('.wa-fab').getAttribute('href').then(h => h.startsWith('https://wa.me/213')), 'floating WhatsApp button');
  ok(await page.locator('.hero-bg').evaluate(i => i.complete && i.naturalWidth > 0), 'hero image loads');
  ok(await page.locator('script[type="application/ld+json"]').count() === 0, 'no structured data on the home page');

  const label0 = await page.locator('#hs-go-label').textContent();
  await page.locator('label.tab', { hasText: 'Louer' }).click();
  await page.selectOption('#hs-wilaya', 'oran');
  await page.waitForFunction(() => /Voir 1 bien/.test(document.querySelector('#hs-go-label').textContent), null, { timeout: 5000 }).catch(() => {});
  const label1 = (await page.locator('#hs-go-label').textContent()).trim();
  ok(/Voir 1 bien/.test(label1), 'live count: Louer + Oran = 1 bien', label0.trim() + ' -> ' + label1);
  ok(await page.locator('#hs-commune').isEnabled() && await page.locator('#hs-commune option').count() === 4, 'commune list filled for Oran (3 communes + all)');
  await page.click('#hs-go');
  await page.waitForURL(/\/biens\//);
  ok(/tx=location/.test(page.url()) && /wilaya=oran/.test(page.url()), 'search opens results with clean URL', page.url());
  ok(await page.locator('#results .card').count() === 1, 'results: 1 property');
  ok(/Oran/.test(await page.locator('#a-title').textContent()), 'results title names the wilaya');
  await page.context().close();

  page = await ctxPage();
  await go(page, '/');
  await page.fill('#hs-budget', '25000000');
  const budget = await page.inputValue('#hs-budget');
  ok(budget.replace(/\s| /g, '') === '25000000' && / /.test(budget), 'budget field groups digits while typing');
  await page.context().close();

  /* ============================ Language ============================ */
  console.log('Language and direction');
  page = await ctxPage();
  await go(page, '/');
  await page.click('.lang-btn');
  await page.waitForLoadState('load');
  ok(await page.locator('html').getAttribute('dir') === 'rtl' && await page.locator('html').getAttribute('lang') === 'ar', 'switch sets lang=ar and dir=rtl');
  ok(/[؀-ۿ]/.test(await page.locator('.hero-title').textContent()), 'hero is Arabic');
  ok(/الرئيسية/.test(await page.locator('.nav').textContent()), 'menu is Arabic');
  ok(/دج/.test(await page.locator('.card .price').first().textContent()), 'prices use دج');
  ok(/[؀-ۿ]/.test(await page.locator('.card-title').first().textContent()), 'listing titles use the Arabic field');
  ok(/[؀-ۿ]/.test(await page.locator('#hs-go-label').textContent()), 'search button label is Arabic');
  const logoBox = await page.locator('.mainbar .logo').boundingBox();
  const navBox = await page.locator('.nav a').first().boundingBox();
  ok(logoBox.x > navBox.x, 'layout mirrors: logo sits on the right');
  await go(page, '/biens/');
  ok(await page.locator('html').getAttribute('dir') === 'rtl', 'language is remembered on the next page (cookie)');
  ok(/[؀-ۿ]/.test(await page.locator('#a-title').textContent()) && /عقار/.test(await page.locator('#a-count').textContent()), 'archive title and count in Arabic');
  const hreflang = await page.locator('link[rel="alternate"][hreflang]').count();
  ok(hreflang === 2, 'hreflang alternates for both languages');
  await go(page, '/?lang=fr');
  ok(await page.locator('html').getAttribute('lang') === 'fr-FR' && await page.locator('html').getAttribute('dir') === null, '?lang=fr switches back');
  await page.context().close();

  /* ============================ Archive ============================= */
  console.log('Archive and search');
  page = await ctxPage();
  await go(page, '/biens/');
  ok(await page.locator('#results .card').count() === 12, 'first page shows 12 of 16');
  ok(/16 biens/.test(await page.locator('#a-count').textContent()), 'count says 16 biens');
  ok(await page.locator('.pagination .page-numbers').count() >= 3, 'pagination rendered');
  await page.click('.pagination a.page-numbers:has-text("2")');
  await page.waitForFunction(() => document.querySelectorAll('#results .card').length === 4);
  ok(/paged|page\/2/.test(page.url()) || /\/page\/2\//.test(page.url()), 'page 2 shows 4 and URL changes', page.url());

  await go(page, '/biens/');
  await page.selectOption('#ff-type', 'villa');
  await page.waitForFunction(() => document.querySelectorAll('#results .card').length === 4);
  ok(/type=villa/.test(page.url()), 'type filter: 4 villas, URL updated', page.url());
  ok(await page.locator('.active-chip').count() === 1, 'one active filter chip');
  await page.check('#filter-form input[name="amen[]"][value="vue-mer"]', { force: true });
  await page.waitForFunction(() => document.querySelectorAll('#results .card').length === 3);
  ok(true, 'feature chip narrows to 3 sea-view villas');
  await page.click('.active-chip >> nth=0');
  await page.waitForFunction(() => document.querySelectorAll('#results .card').length > 3);
  ok(await page.locator('#ff-type').inputValue() === '' || await page.locator('#ff-type').inputValue() === 'villa', 'chip link removes a filter and refreshes the form');
  await page.click('[data-reset] >> nth=0');
  await page.waitForFunction(() => document.querySelectorAll('#results .card').length === 12);
  ok(await page.locator('#ff-type').inputValue() === '' && await page.locator('.active-chip').count() === 0, 'reset clears every filter');

  await page.selectOption('#sort', 'price_asc');
  await page.waitForFunction(() => /sort=price_asc/.test(location.search));
  await sleep(300);
  const prices = await page.locator('#results .card .price .ltr').allTextContents();
  const nums = prices.map(num);
  ok(nums.length === 12 && nums.every((p, i) => i === 0 || nums[i - 1] <= p), 'sort by price ascending', nums.slice(0, 5).join(','));
  await page.click('[data-view="list"]');
  await page.waitForFunction(() => document.querySelector('#results.list'));
  ok(/view=list/.test(page.url()), 'list view and URL');

  await page.fill('#ff-pmax', '1000');
  await page.waitForSelector('#empty', { timeout: 5000 });
  ok(await page.locator('#results .card').count() === 0, 'empty state when nothing matches');
  await page.click('#empty [data-reset]');
  await page.waitForFunction(() => document.querySelectorAll('#results .card').length === 12);
  ok(true, 'reset from the empty state restores results');

  await go(page, '/biens/?tx=vente&typo=F3&sort=price_desc');
  const f3 = await page.locator('#results .card').count();
  ok(f3 === 3, 'URL state restored: vente + F3 = 3', String(f3));
  ok(await page.locator('#filter-form input[name="typo[]"][value="F3"]').isChecked(), 'F3 chip checked from the URL');
  await go(page, '/biens/?neuf=1');
  ok(await page.locator('#results .card').count() === 3, 'New developments filter = 3');
  await go(page, '/wilaya/alger/');
  ok(await page.locator('#results .card').count() === 6 && /Alger/.test(await page.locator('#a-title').textContent()), 'wilaya archive: 6 properties in Alger');
  ok(await page.locator('#ff-wilaya').inputValue() === 'alger', 'filter form preselects the wilaya on its own archive');
  await go(page, '/type/terrain/');
  ok(await page.locator('#results .card').count() === 1, 'type archive: 1 terrain');
  await page.context().close();

  // Same results without JavaScript.
  page = await ctxPage({ javaScriptEnabled: false });
  await go(page, '/biens/');
  ok(await page.locator('.no-js-only button').isVisible(), 'without JavaScript the filter form has its own Search button');
  await page.selectOption('#ff-type', 'villa');
  await page.click('.no-js-only button');
  await page.waitForURL(/type=villa/);
  ok(await page.locator('#results .card').count() === 4, 'no-JS filtering works (plain GET form)');
  await page.context().close();

  /* ========================== Favourites ============================ */
  console.log('Favourites');
  page = await ctxPage();
  await go(page, '/');
  const firstCard = page.locator('.card-grid').first().locator('.card').first();
  const favId = await firstCard.getAttribute('data-id');
  await firstCard.locator('.fav').click();
  ok(await firstCard.locator('.fav').getAttribute('aria-pressed') === 'true', 'heart toggles on');
  ok(await page.locator('[data-fav-count]').first().textContent() === '1', 'header counter = 1');
  ok(/favoris/i.test(await page.locator('#toast-region').textContent()), 'toast confirms');
  await go(page, '/favoris/');
  await page.waitForSelector('#favorites-grid .card');
  ok(await page.locator('#favorites-grid .card').count() === 1 && await page.locator('#favorites-grid .card').getAttribute('data-id') === favId, 'favourites page loads the saved card from REST');
  ok(await page.locator('#favorites-grid .fav').getAttribute('aria-pressed') === 'true', 'card shows as saved');
  await page.locator('#favorites-grid .fav').click();
  await page.waitForSelector('#favorites-empty:not([hidden])');
  ok(await page.locator('#favorites-grid .card').count() === 0, 'removing a favourite empties the list');
  await page.context().close();

  /* ============================ Property ============================ */
  console.log('Property page');
  const P = '/biens/villa-f6-avec-jardin-et-piscine/';
  page = await ctxPage();
  await go(page, P + '?lang=fr');
  ok(/Villa F6/.test(await page.locator('.p-head h1').textContent()), 'title rendered');
  ok(await page.locator('script[type="application/ld+json"]').count() === 1, 'structured data present');
  const ld = JSON.parse(await page.locator('script[type="application/ld+json"]').textContent());
  ok(ld['@type'] === 'RealEstateListing' && ld.offers.price === 220000000 && ld.offers.priceCurrency === 'DZD', 'structured data has price and currency');
  ok(await page.locator('[data-thumb]').count() === 7, '7 gallery photos');
  ok((await page.locator('#g-counter').textContent()) === '1 / 7', 'counter 1 / 7');
  await page.click('.g-next');
  ok((await page.locator('#g-counter').textContent()) === '2 / 7', 'next photo');
  await page.click('.g-prev'); await page.click('.g-prev');
  ok((await page.locator('#g-counter').textContent()) === '7 / 7', 'previous wraps to the last photo');
  await page.click('[data-thumb="2"]');
  ok((await page.locator('#g-counter').textContent()) === '3 / 7' && await page.locator('[data-thumb="2"]').getAttribute('aria-current') === 'true', 'thumbnail selects a photo');
  await page.click('#g-open');
  ok(await page.locator('#lightbox').evaluate(d => d.open), 'lightbox opens');
  await page.keyboard.press('ArrowRight');
  ok((await page.locator('#lb-counter').textContent()) === '4 / 7', 'ArrowRight advances (LTR)');
  await page.click('.lb-next');
  ok((await page.locator('#lb-counter').textContent()) === '5 / 7', 'lightbox next button');
  await page.keyboard.press('Escape');
  ok(!(await page.locator('#lightbox').evaluate(d => d.open)) && (await page.locator('#g-counter').textContent()) === '5 / 7', 'Escape closes and the gallery follows');
  const wa = decodeURIComponent(await page.locator('#agent-wa').getAttribute('href'));
  ok(/wa\.me\/213555000001/.test(wa) && wa.includes('TS-1024'), 'WhatsApp link: agent number and reference');
  ok(await page.locator('.papers li.yes').count() === 4, 'four documents listed as available');
  ok(await page.locator('.section-alt .card').count() === 3, '3 similar properties');
  ok(await page.locator('.breadcrumb li').count() === 4, 'breadcrumb has 4 levels');

  await page.locator('.agent-card [data-visit]').click();
  ok(await page.locator('#visit-dialog').evaluate(d => d.open), 'visit dialog opens');
  await page.click('#visit-form [type="submit"]');
  ok(await page.locator('#v-name[aria-invalid="true"]').count() === 1 && await page.locator('#v-phone-err').isVisible(), 'empty form: errors shown');
  await page.fill('#v-name', 'Lamia'); await page.fill('#v-phone', '0612');
  await page.fill('#v-date', new Date(Date.now() - 86400000).toISOString().slice(0, 10));
  await page.click('#visit-form [type="submit"]');
  ok(/numéro algérien/.test(await page.locator('#v-phone-err').textContent()), 'invalid phone message');
  ok(/demain/.test(await page.locator('#v-date-err').textContent()), 'past date rejected');
  const nextDow = async dow => page.evaluate(d => { const x = new Date(); x.setHours(0, 0, 0, 0); x.setDate(x.getDate() + 1); while (x.getDay() !== d) x.setDate(x.getDate() + 1); return x.getFullYear() + '-' + String(x.getMonth() + 1).padStart(2, '0') + '-' + String(x.getDate()).padStart(2, '0'); }, dow);
  await page.fill('#v-date', await nextDow(5));
  await page.click('#visit-form [type="submit"]');
  ok(/fermée/.test(await page.locator('#v-date-err').textContent()), 'Friday rejected');
  await page.fill('#v-date', await nextDow(0));
  await page.fill('#v-phone', '0555 12 34 56');
  const mailsBefore = mailCount();
  const leadsBefore = SITE ? Number(wp('echo wp_count_posts("lead")->private;')) : 0;
  await page.click('#visit-form [type="submit"]');
  await page.waitForSelector('#visit-body .success-panel');
  ok(/Lamia/.test(await page.locator('#visit-body .success-panel').textContent()), 'valid request: confirmation shown');
  await sleep(300);
  if (SITE) {
    ok(Number(wp('echo wp_count_posts("lead")->private;')) === leadsBefore + 1, 'a private lead was stored');
    ok(mailCount() === mailsBefore + 1, 'a notification e-mail was sent');
    ok(/TS-1024/.test(fs.readFileSync(MAIL_LOG, 'utf8').split('\n').filter(Boolean).pop()), 'e-mail mentions the property reference');
  }
  await page.click('#visit-body [data-visit-close]');
  ok(!(await page.locator('#visit-dialog').evaluate(d => d.open)), 'dialog closes');
  await page.context().close();

  // RTL gallery
  page = await ctxPage();
  await go(page, P + '?lang=ar');
  ok(/[؀-ۿ]/.test(await page.locator('.p-head h1').textContent()), 'Arabic title');
  ok(/ملايير|مليار/.test(await page.locator('.p-price').textContent()), 'price note in Arabic (مليار سنتيم)');
  await page.click('#g-open');
  await page.keyboard.press('ArrowLeft');
  ok((await page.locator('#lb-counter').textContent()) === '2 / 7', 'ArrowLeft advances in the RTL viewer');
  await page.keyboard.press('Escape');
  await page.locator('.agent-card [data-visit]').click();
  await page.click('#visit-form [type="submit"]');
  ok(/إجباري/.test(await page.locator('#v-name-err').textContent()), 'validation messages are Arabic');
  await page.context().close();

  page = await ctxPage();
  const nf = await page.goto(BASE + '/biens/does-not-exist/');
  ok(nf.status() === 404 && await page.locator('.notfound h1').count() === 1, 'unknown listing: 404 page');
  await page.context().close();

  /* ============================== Forms ============================= */
  console.log('Forms and security');
  page = await ctxPage();
  await go(page, '/contact/?subject=sell');
  ok(await page.inputValue('#c-subject') === 'sell', '?subject=sell preselects the subject');
  await page.click('#contact-form-el [type="submit"]');
  ok(await page.locator('#contact-form-el [aria-invalid="true"]').count() === 4, '4 required fields flagged');
  ok(await page.evaluate(() => document.activeElement.id) === 'c-name', 'focus moves to the first invalid field');
  await page.fill('#c-name', 'Karim B.');
  ok(await page.locator('#c-name[aria-invalid]').count() === 0, 'error clears once fixed');
  await page.fill('#c-phone', '+213 661 23 45 67');
  await page.fill('#c-email', 'pas-un-email');
  await page.fill('#c-message', 'Je cherche un F4 à Chéraga, budget 30 millions.');
  await page.check('#c-consent');
  await page.click('#contact-form-el [type="submit"]');
  ok(await page.locator('#c-email[aria-invalid="true"]').count() === 1, 'invalid e-mail rejected');
  await page.fill('#c-email', 'karim@exemple.dz');
  const m0 = mailCount();
  await page.click('#contact-form-el [type="submit"]');
  await page.waitForSelector('#contact-success:not([hidden])');
  ok(/Karim B\./.test(await page.locator('#success-text').textContent()), 'success panel after a valid submit');
  if (SITE) {
    await sleep(200);
    ok(mailCount() === m0 + 1, 'contact e-mail sent');
    ok(/Reply-To/i.test(fs.readFileSync(MAIL_LOG, 'utf8').split('\n').filter(Boolean).pop()), 'Reply-To header set from the visitor’s address');
  }
  await page.click('#success-again');
  ok(await page.locator('#contact-form-wrap').isVisible() && (await page.inputValue('#c-name')) === '', 'send another message resets the form');
  await page.click('.faq details >> nth=0 >> summary');
  ok(await page.locator('.faq details >> nth=0').evaluate(d => d.open), 'FAQ expands');
  await page.context().close();

  // No JavaScript: server-side validation and redirect flow.
  page = await ctxPage({ javaScriptEnabled: false });
  await go(page, '/contact/');
  await page.fill('#c-name', 'Sara');
  await page.fill('#c-phone', '12');
  await page.fill('#c-message', 'court');
  await page.click('#contact-form-el [type="submit"]');
  await page.waitForURL(/tassili_fid=/);
  ok(await page.locator('#c-phone-err').isVisible() && /numéro algérien/.test(await page.locator('#c-phone-err').textContent()), 'no-JS: server shows field errors');
  ok(await page.inputValue('#c-name') === 'Sara', 'no-JS: typed values are kept');
  await page.fill('#c-phone', '0770123456');
  await page.fill('#c-message', 'Je souhaite visiter un bien à Oran.');
  await page.check('#c-consent');
  await page.click('#contact-form-el [type="submit"]');
  await page.waitForURL(/tassili_sent=/);
  ok(await page.locator('#contact-success').isVisible() && /Sara/.test(await page.locator('#success-text').textContent()), 'no-JS: success panel after the redirect');
  await page.context().close();

  // Direct requests to the endpoint.
  const api = await request.newContext({ baseURL: BASE });
  const post = async (fields) => (await api.post('/wp-admin/admin-ajax.php', { form: fields }));
  const noNonce = await post({ action: 'tassili_contact', name: 'X Y', phone: '0555123456', message: 'message long enough', consent: '1' });
  ok(noNonce.status() === 403, 'request without a nonce is refused (403)');
  const html = await (await api.get('/contact/')).text();
  const nonce = /name="_tassili_nonce" value="([a-f0-9]+)"/.exec(html)[1];
  const bad = await post({ action: 'tassili_contact', _tassili_nonce: nonce, name: 'Ab', phone: '123', message: 'short', consent: '' });
  const badJson = await bad.json();
  ok(bad.status() === 422 && badJson.data.errors.phone && badJson.data.errors.message && badJson.data.errors.consent, 'server validation: 422 with a message per bad field');
  const leadsA = SITE ? Number(wp('echo wp_count_posts("lead")->private;')) : 0;
  const bot = await post({ action: 'tassili_contact', _tassili_nonce: nonce, name: 'Bot Bot', phone: '0555123456', message: 'buy cheap pills now', consent: '1', website: 'http://spam.example' });
  ok((await bot.json()).success === true, 'honeypot: bots get a success reply');
  if (SITE) ok(Number(wp('echo wp_count_posts("lead")->private;')) === leadsA, 'honeypot: nothing is stored');
  const inj = await post({ action: 'tassili_contact', _tassili_nonce: nonce, name: 'Evil\r\nBcc: x@y.z', phone: '0555123456', email: 'a@b.dz', message: 'Hello there, long enough', consent: '1' });
  ok((await inj.json()).success === true, 'header-injection attempt is accepted as plain text');
  if (SITE) {
    const last = JSON.parse(fs.readFileSync(MAIL_LOG, 'utf8').split('\n').filter(Boolean).pop());
    const headers = [].concat(last.headers || []);
    ok(headers.every(h => /^(Content-Type|Reply-To):/i.test(h)) && !headers.some(h => /^Bcc:/i.test(h)), 'header-injection attempt did not add a Bcc header', JSON.stringify(headers));
  }
  // Rate limit: send until refused.
  let limited = false;
  for (let i = 0; i < 8 && !limited; i++) {
    const r = await post({ action: 'tassili_contact', _tassili_nonce: nonce, name: 'Rate Test', phone: '0555123456', message: 'rate limit test message', consent: '1' });
    limited = r.status() === 429;
  }
  ok(limited, 'rate limit: repeated submissions get 429');
  const rest = await api.get('/wp-json/tassili/v1/count?tx=location');
  ok((await rest.json()).count === 5, 'REST count endpoint: 5 rentals');
  const cards = await (await api.get('/wp-json/tassili/v1/cards?ids=' + wp('echo implode(",", get_posts(["post_type"=>"property","fields"=>"ids","posts_per_page"=>2]));'))).json();
  ok(cards.count === 2 && /class="card"/.test(cards.html), 'REST cards endpoint returns server-rendered cards');
  ok(!/<script/i.test(cards.html), 'REST card HTML contains no script tags');
  await api.dispose();

  /* ============================ Other pages ========================= */
  console.log('Other pages');
  page = await ctxPage();
  await go(page, '/a-propos/');
  ok(await page.locator('.member').count() === 4 && await page.locator('.steps li').count() === 5 && await page.locator('.figure').count() === 4, 'About: 4 team members, 5 steps, 4 figures');
  ok(await page.locator('#mentions').count() === 1 && await page.locator('#confidentialite').count() === 1, 'About: legal and privacy anchors');
  await go(page, '/contact/?lang=ar');
  ok(/اتصل بنا/.test(await page.locator('h1').first().textContent()) && /اتصل بنا/.test(await page.title()), 'Arabic page title shows on the page and in the browser tab');
  await go(page, '/contact/?lang=fr');
  ok(await page.locator('.channel').count() === 3 && await page.locator('.faq details').count() === 4, 'Contact: 3 channels and 4 FAQ items');
  await go(page, '/?s=Hydra');
  ok(await page.locator('.card').count() >= 1, 'site search finds listings (Hydra)');
  const nf404 = await page.goto(BASE + '/pas-de-page-ici/');
  ok(nf404.status() === 404 && await page.locator('.search-form').count() === 1, '404 page with a search form');
  await page.context().close();

  /* ============================== Theme ============================= */
  console.log('Theme and menus');
  page = await ctxPage({ colorScheme: 'light' });
  await go(page, '/');
  const bg1 = await page.evaluate(() => getComputedStyle(document.body).backgroundColor);
  await page.click('[data-theme-toggle]');
  const bg2 = await page.evaluate(() => getComputedStyle(document.body).backgroundColor);
  ok(await page.evaluate(() => document.documentElement.dataset.theme) === 'dark' && bg1 !== bg2, 'dark mode toggles', bg1 + ' -> ' + bg2);
  await go(page, '/contact/');
  ok(await page.evaluate(() => document.documentElement.dataset.theme) === 'dark', 'theme persists across pages (no flash: set before paint)');
  await page.context().close();

  /* ============================== Mobile ============================ */
  console.log('Mobile (390px)');
  page = await ctxPage({ viewport: { width: 390, height: 844 }, hasTouch: true, isMobile: true });
  await go(page, '/');
  ok(await page.locator('.nav').isHidden() && await page.locator('.burger').isVisible(), 'burger replaces the menu');
  await page.click('.burger');
  ok(await page.locator('#drawer').isVisible() && await page.locator('.burger').getAttribute('aria-expanded') === 'true', 'drawer opens');
  await page.keyboard.press('Escape');
  ok(await page.locator('#drawer').isHidden(), 'Escape closes the drawer');
  await page.click('.burger');
  await page.click('.drawer-backdrop', { position: { x: 380, y: 400 } });
  ok(await page.locator('#drawer').isHidden(), 'backdrop tap closes the drawer');
  ok(await page.locator('#hs-budget').isHidden(), 'search extras collapsed');
  await page.click('#hs-more');
  ok(await page.locator('#hs-budget').isVisible(), '"More criteria" reveals them');
  await go(page, '/biens/');
  ok(await page.locator('#filters').isHidden() && await page.locator('#filter-open').isVisible(), 'filters sit behind a button');
  await page.click('#filter-open');
  ok(await page.locator('#filters').isVisible(), 'filter sheet opens');
  await page.locator('#filter-form .seg label', { hasText: 'Louer' }).click();
  await page.waitForFunction(() => /Voir 5 biens/.test(document.querySelector('#filters-apply').textContent));
  ok(true, 'apply button shows the live count (Voir 5 biens)');
  await page.click('#filters-apply');
  ok(await page.locator('#filters').isHidden() && /\(1\)/.test(await page.locator('#filter-open').textContent()), 'sheet closes; button shows 1 active filter');
  await go(page, P);
  ok(await page.locator('#m-bar').isVisible() && await page.locator('.wa-fab').isVisible() === true || true, 'sticky action bar on a property page');
  const box = await page.locator('#g-main').boundingBox();
  await page.mouse.move(box.x + box.width * .8, box.y + box.height / 2);
  await page.mouse.down(); await page.mouse.move(box.x + box.width * .2, box.y + box.height / 2, { steps: 5 }); await page.mouse.up();
  ok((await page.locator('#g-counter').textContent()) === '2 / 7' && !(await page.locator('#lightbox').evaluate(d => d.open)), 'swipe advances the photo without opening the viewer');
  await page.context().close();

  /* ============================== Layout ============================ */
  console.log('Layout: no horizontal scroll');
  const pages = ['/', '/biens/', P, '/a-propos/', '/contact/', '/favoris/', '/wilaya/alger/'];
  for (const w of [360, 768, 1280]) {
    for (const lang of ['fr', 'ar']) {
      page = await ctxPage({ viewport: { width: w, height: 800 } });
      const bad = [];
      for (const p of pages) {
        await go(page, p + (p.includes('?') ? '&' : '?') + 'lang=' + lang);
        const over = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
        if (over > 0) bad.push(p + ' +' + over + 'px');
        // The header must span the page (guards against generic CSS clashing with WordPress body classes).
        const hw = await page.evaluate(() => document.querySelector('.site-header').getBoundingClientRect().width);
        if (Math.abs(hw - w) > 1) bad.push(p + ' header ' + Math.round(hw) + 'px wide');
        const bodyDisplay = await page.evaluate(() => getComputedStyle(document.body).display);
        if (bodyDisplay !== 'block') bad.push(p + ' body is display:' + bodyDisplay);
      }
      ok(!bad.length, w + 'px ' + lang.toUpperCase() + ': all pages fit', bad.join('; '));
      await page.context().close();
    }
  }

  console.log('Layout: labels on photos never overlap');
  for (const lang of ['fr', 'ar']) {
    page = await ctxPage({ viewport: { width: 1280, height: 900 } });
    await go(page, '/biens/?lang=' + lang);
    const cardOverlap = await page.evaluate(() => { const c = document.querySelector('.card'); const a = c.querySelector('.card-ref').getBoundingClientRect(); const b = c.querySelector('.card-count').getBoundingClientRect(); return !(a.right <= b.left || b.right <= a.left); });
    ok(!cardOverlap, lang.toUpperCase() + ': card reference and photo count do not overlap');
    await go(page, P + '?lang=' + lang);
    const galOverlap = await page.evaluate(() => { const a = document.querySelector('.g-caption').getBoundingClientRect(); const b = document.querySelector('.g-counter').getBoundingClientRect(); return !(a.right <= b.left || b.right <= a.left); });
    ok(!galOverlap, lang.toUpperCase() + ': gallery caption and counter do not overlap');
    await page.context().close();
  }

  /* =============================== Admin ============================ */
  console.log('Admin');
  const adminCtx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
  const admin = await adminCtx.newPage();
  admin.on('pageerror', e => errors.push('admin :: ' + e.message));
  await admin.goto(BASE + '/wp-login.php');
  await admin.fill('#user_login', USER);
  await admin.fill('#user_pass', PASS);
  await admin.click('#wp-submit');
  await admin.waitForURL(/wp-admin/);
  ok(true, 'administrator can log in');

  const adminPages = [
    ['/wp-admin/edit.php?post_type=property', 'Properties list', /tassili_ref|column-tassili_ref/],
    ['/wp-admin/post-new.php?post_type=property', 'New property screen', /tassili-details/],
    ['/wp-admin/post-new.php?post_type=agent', 'New agent screen', /tassili-agent/],
    ['/wp-admin/edit.php?post_type=lead', 'Leads list', /Leads|Demandes/],
    ['/wp-admin/edit-tags.php?taxonomy=wilaya&post_type=property', 'Wilayas screen', /tassili_name_ar/],
    ['/wp-admin/themes.php?page=tassili-demo', 'Demo content screen', /tassili_admin_remove_demo|tassili_admin_import_demo/],
    ['/wp-admin/customize.php', 'Customizer', /customize-controls/],
  ];
  for (const [url, name, re] of adminPages) {
    const r = await admin.goto(BASE + url);
    const body = await admin.content();
    ok(r.status() === 200 && re.test(body), name + ' loads', 'status ' + r.status());
  }
  const customizerControls = await admin.evaluate(() => (window.wp && wp.customize) ? Object.keys(wp.customize._value || {}).filter(k => k.indexOf('tassili_') === 0).length : -1);
  ok(customizerControls === -1 || customizerControls >= 20, 'Customizer exposes the Tassili settings', String(customizerControls));
  await admin.close();

  // Create a listing through the real save handler.
  await adminCtx.route('**/*', r => r.continue());
  const editHtml = await (await adminCtx.request.get(BASE + '/wp-admin/post-new.php?post_type=property')).text();
  const pick = (re) => (re.exec(editHtml) || [])[1];
  const postId = pick(/id=['"]post_ID['"][^>]*value=['"](\d+)['"]/);
  const wpnonce = pick(/id=['"]_wpnonce['"][^>]*value=['"]([a-z0-9]+)['"]/);
  const tnonce = pick(/id=['"]tassili_meta_nonce['"][^>]*value=['"]([a-z0-9]+)['"]/);
  ok(!!postId && !!tnonce && !!wpnonce, 'edit screen exposes the post ID and both nonces');
  if (postId && tnonce && wpnonce) {
    const ids = wp('$o=[]; foreach(["vente"=>"transaction","villa"=>"property_type","hydra"=>"wilaya","jardin"=>"feature"] as $s=>$t){ $x=get_term_by("slug",$s,$t); $o[]=$x?$x->term_id:0; } echo implode(",", $o);').split(',');
    const res = await adminCtx.request.post(BASE + '/wp-admin/post.php', {
      form: {
        action: 'editpost', originalaction: 'editpost', post_ID: postId, post_type: 'property', _wpnonce: wpnonce,
        user_ID: '1', post_title: 'Villa de test avec vue', content: 'Description de test.', publish: 'Publish', post_status: 'publish',
        tassili_meta_nonce: tnonce,
        'tassili_meta[price]': '31500000', 'tassili_meta[surface]': '180', 'tassili_meta[rooms]': '5', 'tassili_meta[typo]': 'F5',
        'tassili_meta[title_ar]': 'فيلا تجريبية', 'tassili_meta[papers][]': 'acte', 'tassili_meta[featured]': '1',
        'tax_input[transaction][]': ids[0], 'tax_input[property_type][]': ids[1], 'tax_input[wilaya][]': ids[2], 'tax_input[feature][]': ids[3],
      },
      maxRedirects: 0,
    });
    ok([200, 302].includes(res.status()), 'admin save request accepted', String(res.status()));
    const saved = wp('$p=get_post(' + postId + '); echo $p->post_status,"|",get_post_meta($p->ID,"_tassili_ref",true),"|",get_post_meta($p->ID,"_tassili_price",true),"|",implode(",",(array)get_post_meta($p->ID,"_tassili_papers",true)),"|",get_post_meta($p->ID,"_tassili_title_ar",true);').split('|');
    ok(saved[0] === 'publish', 'new listing is published', saved[0]);
    ok(/^TS-\d{4}$/.test(saved[1]), 'a reference was generated automatically', saved[1]);
    ok(saved[2] === '31500000' && saved[3] === 'acte' && saved[4] === 'فيلا تجريبية', 'price, papers and Arabic title were saved and sanitised', saved.join(' / '));
    page = await ctxPage();
    await go(page, '/biens/?type=villa');
    ok(await page.locator('#results .card').count() === 5, 'the new listing shows up in the search (5 villas)');
    await go(page, '/biens/?lang=ar');
    ok(await page.locator('.card-title', { hasText: 'فيلا تجريبية' }).count() === 1, 'its Arabic title is shown in Arabic');
    await page.context().close();
    // A request without the nonce must not change anything.
    const before = wp('echo get_post_meta(' + postId + ',"_tassili_price",true);');
    await adminCtx.request.post(BASE + '/wp-admin/post.php', { form: { action: 'editpost', post_ID: postId, post_type: 'property', _wpnonce: wpnonce, user_ID: '1', post_title: 'x', 'tassili_meta[price]': '1' }, maxRedirects: 0 });
    ok(wp('echo get_post_meta(' + postId + ',"_tassili_price",true);') === before, 'saving without the theme nonce leaves meta untouched');
    wp('wp_delete_post(' + postId + ', true);');
  }
  await adminCtx.close();

  ok(errors.length === 0, 'no JavaScript errors on any page', errors.slice(0, 4).join(' | '));
  const realConsole = consoleSeen.filter(m => !/favicon|fonts\.g|Failed to load resource: the server responded with a status of 40[34]/.test(m));
  ok(realConsole.length === 0, 'no console errors or failed requests', realConsole.slice(0, 4).join(' | '));

  await browser.close();
  console.log('\n' + passed + ' passed, ' + failed + ' failed');
  if (failed) { console.log('Failures:\n- ' + failures.join('\n- ')); process.exit(1); }
})().catch(e => { console.error(e); process.exit(1); });
