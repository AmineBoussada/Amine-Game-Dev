// Builds a single-file HTML preview (hash routing, inlined JS/CSS) for static sharing.
import { execSync } from 'node:child_process';
import { readFileSync, readdirSync, writeFileSync } from 'node:fs';

execSync('npx vite build --mode preview-single', { stdio: 'inherit', env: { ...process.env, VITE_HASH_ROUTER: 'true', VITE_ILLUSTRATIONS_ONLY: 'true' } });
const dir = 'dist-preview/assets';
const files = readdirSync(dir);
const js = readFileSync(`${dir}/${files.find((f) => f.endsWith('.js'))}`, 'utf8').replace(/<\/script/gi, '<\\/script');
const css = readFileSync(`${dir}/${files.find((f) => f.endsWith('.css'))}`, 'utf8');

const html = `<title>NOVA IMMOBILIER</title>
<meta name="description" content="Démo du site de l’agence immobilière NOVA IMMOBILIER (Alger) : biens à vendre et à louer, recherche, fiches détaillées et contact.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,600;1,500&display=swap">
<style>${css}</style>
<div id="root"></div>
<script type="module">${js}</script>
`;
writeFileSync('dist-preview/nova-immobilier.html', html);
console.log('dist-preview/nova-immobilier.html', (html.length / 1024).toFixed(0) + ' KB');
