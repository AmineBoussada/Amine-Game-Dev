// Exports src/data/properties.ts to the WordPress theme's seed file (used by `wp nova seed`).
import { build } from 'esbuild';
import { writeFileSync } from 'node:fs';

const out = await build({ entryPoints: ['src/data/properties.ts'], bundle: true, write: false, format: 'esm', platform: 'node' });
const mod = await import('data:text/javascript;base64,' + Buffer.from(out.outputFiles[0].text).toString('base64'));
const data = mod.properties.map(({ images, slug, id, ...p }) => ({ ...p, slug }));
writeFileSync('wordpress/nova-immobilier/inc/seed-data.json', JSON.stringify(data, null, 2));
console.log(`Exported ${data.length} properties`);
