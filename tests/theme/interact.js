const { chromium } = require('playwright');
const B = 'http://127.0.0.1:8080';
let fail = 0;
const ok = (c, m) => { console.log((c ? 'PASS ' : 'FAIL ') + m); if (!c) fail++; };
(async () => {
  const b = await chromium.launch();
  // ---------- Mobile drawer
  const ctx = await b.newContext({ viewport: { width: 390, height: 844 } });
  const p = await ctx.newPage();
  await p.goto(B + '/', { waitUntil: 'networkidle' });
  const toggle = p.locator('[data-ds-drawer-open]');
  ok(await p.locator('#ds-drawer').isHidden(), 'drawer hidden initially');
  ok(await toggle.getAttribute('aria-expanded') === 'false', 'toggle aria-expanded=false initially');
  await toggle.click(); await p.waitForTimeout(350);
  ok(await p.locator('#ds-drawer').isVisible(), 'drawer opens on click');
  ok(await toggle.getAttribute('aria-expanded') === 'true', 'aria-expanded=true when open');
  const panel = await p.locator('.ds-drawer__panel').boundingBox();
  ok(Math.abs((panel.x + panel.width) - 390) < 2, 'RTL: panel docked to the RIGHT edge (x+w=' + Math.round(panel.x + panel.width) + ')');
  ok(await p.evaluate(() => document.documentElement.classList.contains('ds-no-scroll')), 'body scroll locked');
  const focusInPanel = await p.evaluate(() => !!document.activeElement.closest('.ds-drawer__panel'));
  ok(focusInPanel, 'focus moved into drawer');
  // focus trap: tab many times, focus must stay inside
  let stays = true;
  for (let i = 0; i < 25; i++) { await p.keyboard.press('Tab'); if (!(await p.evaluate(() => !!document.activeElement.closest('.ds-drawer__panel')))) stays = false; }
  ok(stays, 'focus trapped inside drawer (Tab x25)');
  stays = true;
  for (let i = 0; i < 6; i++) { await p.keyboard.press('Shift+Tab'); if (!(await p.evaluate(() => !!document.activeElement.closest('.ds-drawer__panel')))) stays = false; }
  ok(stays, 'focus trapped inside drawer (Shift+Tab)');
  // submenu accordion
  const sub = p.locator('.ds-drawer__list .ds-submenu-toggle').first();
  ok(await sub.count() === 1, 'submenu toggle button injected');
  ok(await p.locator('.ds-drawer__list .sub-menu').first().isHidden(), 'submenu collapsed initially');
  await sub.click();
  ok(await p.locator('.ds-drawer__list .sub-menu').first().isVisible(), 'submenu expands');
  ok(await sub.getAttribute('aria-expanded') === 'true', 'submenu aria-expanded=true');
  await p.keyboard.press('Escape'); await p.waitForTimeout(350);
  ok(await p.locator('#ds-drawer').isHidden(), 'Escape closes drawer');
  ok(await p.evaluate(() => document.activeElement === document.querySelector('[data-ds-drawer-open]')), 'focus restored to toggle');
  ok(!(await p.evaluate(() => document.documentElement.classList.contains('ds-no-scroll'))), 'scroll lock released');
  await toggle.click(); await p.waitForTimeout(350);
  await p.mouse.click(30, 400); await p.waitForTimeout(350);
  ok(await p.locator('#ds-drawer').isHidden(), 'overlay click closes drawer');
  await toggle.click(); await p.waitForTimeout(350);
  await p.screenshot({ path: process.argv[2] + '/drawer-open-m.png' });
  await p.locator('.ds-drawer__nav a', { hasText: 'من نحن' }).click(); await p.waitForLoadState('networkidle');
  ok(p.url().includes('/about'), 'drawer link navigates');
  // search row
  await p.goto(B + '/', { waitUntil: 'networkidle' });
  const st = p.locator('[data-ds-search-toggle]');
  await st.click();
  ok(await p.locator('#ds-search-row').isVisible(), 'search row opens');
  ok(await p.evaluate(() => document.activeElement.type === 'search'), 'search input focused');
  await p.keyboard.press('Escape');
  ok(await p.locator('#ds-search-row').isHidden(), 'Escape closes search row');
  // hide on scroll down / show on scroll up
  await p.evaluate(() => window.scrollTo(0, 600)); await p.waitForTimeout(200);
  await p.evaluate(() => window.scrollTo(0, 900)); await p.waitForTimeout(400);
  ok(await p.locator('.ds-header').evaluate(e => e.classList.contains('is-hidden')), 'header hides on scroll down (mobile)');
  await p.evaluate(() => window.scrollTo(0, 700)); await p.waitForTimeout(400);
  ok(!(await p.locator('.ds-header').evaluate(e => e.classList.contains('is-hidden'))), 'header shows on scroll up');
  await ctx.close();

  // ---------- Desktop nav
  const d = await b.newContext({ viewport: { width: 1280, height: 900 } });
  const q = await d.newPage();
  await q.goto(B + '/', { waitUntil: 'networkidle' });
  ok(await q.locator('[data-ds-drawer-open]').isHidden(), 'desktop: hamburger hidden');
  ok(await q.locator('.ds-header__nav').isVisible(), 'desktop: nav visible');
  const subDesk = q.locator('.ds-nav .menu-item-has-children > .sub-menu');
  ok(await subDesk.isHidden(), 'desktop: dropdown closed');
  await q.locator('.ds-nav .menu-item-has-children').hover();
  ok(await subDesk.isVisible(), 'desktop: dropdown opens on hover');
  await q.mouse.move(10, 400);
  ok(await subDesk.isHidden(), 'desktop: dropdown closes on mouse leave');
  await q.locator('.ds-nav .ds-submenu-toggle').focus(); await q.keyboard.press('Enter');
  ok(await subDesk.isVisible(), 'desktop: dropdown opens via keyboard toggle');
  const subBox = await subDesk.boundingBox(); const parentBox = await q.locator('.ds-nav .menu-item-has-children').boundingBox();
  ok(subBox.x + subBox.width <= parentBox.x + parentBox.width + 2, 'RTL: dropdown aligned to parent inline-start (right edge)');
  await q.screenshot({ path: process.argv[2] + '/dropdown-d.png', clip: { x: 0, y: 0, width: 1280, height: 400 } });
  // Resize while drawer open
  await d.close();
  const r = await b.newContext({ viewport: { width: 390, height: 844 } });
  const rp = await r.newPage(); await rp.goto(B + '/', { waitUntil: 'networkidle' });
  await rp.locator('[data-ds-drawer-open]').click(); await rp.waitForTimeout(350);
  await rp.setViewportSize({ width: 1280, height: 900 }); await rp.waitForTimeout(400);
  ok(await rp.locator('#ds-drawer').isHidden(), 'drawer auto-closes when resized to desktop');
  // no-JS fallback: nav still usable
  const nj = await b.newContext({ viewport: { width: 1280, height: 900 }, javaScriptEnabled: false });
  const np = await nj.newPage(); await np.goto(B + '/', { waitUntil: 'load' });
  ok(await np.locator('.ds-header__nav a').first().isVisible(), 'no-JS: desktop nav visible and links work');
  await b.close();
  console.log(fail ? `\n${fail} FAILED` : '\nALL PASSED');
  process.exit(fail ? 1 : 0);
})();
