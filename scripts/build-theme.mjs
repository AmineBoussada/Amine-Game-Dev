// Builds the WordPress theme assets from the shared sources:
//  - theme-src/main.ts → assets/js/theme.js
//  - src/styles/*.css  → assets/css/
//  - src/data/properties.ts → inc/seed-data.json
// With --zip, also creates wordpress/nova-immobilier.zip ready to upload in WordPress.
import { execSync } from 'node:child_process';
import { copyFileSync } from 'node:fs';

const theme = 'wordpress/nova-immobilier';
execSync('npx vite build -c vite.theme.config.ts', { stdio: 'inherit' });
for (const f of ['tokens', 'base', 'components', 'pages']) copyFileSync(`src/styles/${f}.css`, `${theme}/assets/css/${f}.css`);
execSync('node scripts/export-seed.mjs', { stdio: 'inherit' });
if (process.argv.includes('--zip')) {
  execSync(`cd wordpress && rm -f nova-immobilier.zip && zip -qr nova-immobilier.zip nova-immobilier -x '*.DS_Store'`, { stdio: 'inherit' });
  console.log('→ wordpress/nova-immobilier.zip');
}
