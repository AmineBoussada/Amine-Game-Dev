// Installs dist/top-solution-child.zip through the real WordPress admin upload screen.
const { chromium } = require('playwright');
const ZIP = process.argv[2], OUT = process.argv[3];
(async () => {
  const b = await chromium.launch(); const p = await b.newPage({ viewport: { width: 1280, height: 900 } });
  await p.goto('http://127.0.0.1:8080/wp-login.php', { waitUntil: 'load' });
  await p.fill('#user_login', 'admin'); await p.fill('#user_pass', 'admin-pass');
  await Promise.all([p.waitForNavigation({ waitUntil: 'load' }), p.click('#wp-submit')]);
  await p.goto('http://127.0.0.1:8080/wp-admin/theme-install.php?upload', { waitUntil: 'load' });
  await p.setInputFiles('#themezip', ZIP);
  // The upload panel is revealed by JS that needs wordpress.org (blocked here); submit the same form directly.
  await Promise.all([p.waitForNavigation({ timeout: 120000, waitUntil: 'load' }), p.$eval('form.wp-upload-form', f => f.submit())]);
  const body = await p.locator('#wpbody-content').innerText();
  console.log('--- installer output ---\n' + body.split('\n').filter(l => l.trim()).slice(0, 12).join('\n'));
  await p.screenshot({ path: OUT + '/install-result.png' });
  const activate = p.locator('a.activatelink, a:has-text("تفعيل")').first();
  if (await activate.count()) {
    await Promise.all([p.waitForNavigation({ waitUntil: 'load' }), activate.click()]);
    console.log('--- after activate ---\n' + (await p.locator('#message, .notice').first().innerText().catch(() => 'no notice')));
  } else { console.log('NO ACTIVATE LINK'); process.exitCode = 1; }
  await p.goto('http://127.0.0.1:8080/wp-admin/themes.php', { waitUntil: 'load' });
  console.log('active theme card:', (await p.locator('.theme.active .theme-name').innerText()).trim());
  await b.close();
})();
