// Installs and activates a plugin ZIP through the real wp-admin upload screen.
// Usage: node install-plugin-zip.js <zip> <out-dir>
const { chromium } = require('playwright');
const ZIP = process.argv[2], OUT = process.argv[3] || '.';
(async () => {
  const b = await chromium.launch(); const p = await b.newPage({ viewport: { width: 1280, height: 900 } });
  await p.goto('http://127.0.0.1:8080/wp-login.php', { waitUntil: 'load' });
  await p.fill('#user_login', 'admin'); await p.fill('#user_pass', 'admin-pass');
  await Promise.all([p.waitForNavigation({ waitUntil: 'load' }), p.click('#wp-submit')]);
  await p.goto('http://127.0.0.1:8080/wp-admin/plugin-install.php?tab=upload', { waitUntil: 'load' });
  await p.setInputFiles('#pluginzip', ZIP);
  // The upload panel is revealed by JS that needs wordpress.org (blocked in the sandbox); submit the same form.
  await Promise.all([p.waitForNavigation({ timeout: 120000, waitUntil: 'load' }), p.$eval('form.wp-upload-form', f => f.submit())]);
  const body = await p.locator('#wpbody-content').innerText();
  console.log('--- installer output ---\n' + body.split('\n').filter(l => l.trim()).slice(0, 10).join('\n'));
  await p.screenshot({ path: OUT + '/plugin-install-result.png' });
  const activate = p.locator('a.button-primary[href*="action=activate"]').first();
  if (await activate.count()) {
    await Promise.all([p.waitForNavigation({ waitUntil: 'load' }), activate.click()]);
    console.log('--- after activate ---\n' + (await p.locator('#message, .notice').first().innerText().catch(() => 'no notice')));
  } else { console.log('NO ACTIVATE LINK'); process.exitCode = 1; }
  await b.close();
})();
