// Generates wordpress/nova-immobilier/assets/css/modes.css from the theme stylesheets:
//  - right-to-left overrides ([dir="rtl"]) mirroring every left/right rule, for the Arabic site;
//  - dark mode ([data-theme="sombre"]): tokens plus fixes for elements whose colours must not invert.
// Run by scripts/build-theme.mjs after the shared CSS is copied. Do not edit modes.css by hand.
import { readFileSync, writeFileSync } from 'node:fs';

const DIR = 'wordpress/nova-immobilier/assets/css';
const FILES = ['tokens', 'base', 'components', 'pages', 'theme'];

/* ---------- Tiny CSS parser: rules with their @media context ---------- */
function parse(css) {
  css = css.replace(/\/\*[\s\S]*?\*\//g, '');
  const rules = [];
  const walk = (src, media) => {
    let i = 0;
    while (i < src.length) {
      const open = src.indexOf('{', i);
      if (open === -1) break;
      const head = src.slice(i, open).trim();
      let depth = 1, j = open + 1;
      while (j < src.length && depth) {
        if (src[j] === '{') depth++;
        else if (src[j] === '}') depth--;
        j++;
      }
      const body = src.slice(open + 1, j - 1);
      if (head.startsWith('@media')) walk(body, head);
      else if (!head.startsWith('@')) {
        const decls = body.split(';').map((d) => d.trim()).filter(Boolean).map((d) => {
          const k = d.indexOf(':');
          return [d.slice(0, k).trim(), d.slice(k + 1).trim()];
        });
        rules.push({ media, selectors: head.split(',').map((s) => s.trim()).filter(Boolean), decls });
      }
      i = j;
    }
  };
  walk(css, null);
  return rules;
}

const prefix = (sels, p) => sels.map((s) => (s.startsWith(':root') ? s.replace(':root', `:root${p}`) : s === 'html' ? `html${p}` : `${p} ${s}`)).join(',\n');
const wrap = (media, block) => (media ? `${media} {\n${block.replace(/^/gm, '  ')}\n}` : block);

/* ---------- RTL ---------- */
const swapSide = (prop) => prop.replace(/left|right/, (m) => (m === 'left' ? 'right' : 'left'));
function rtlDecls(decls) {
  const out = new Map();
  const has = Object.fromEntries(decls);
  for (const [prop, val] of decls) {
    if (/^(left|right)$/.test(prop)) {
      const other = swapSide(prop);
      out.set(other, val);
      if (!(other in has)) out.set(prop, 'auto');
    } else if (/^(margin|padding|border)-(left|right)(-|$)/.test(prop)) {
      const other = swapSide(prop);
      out.set(other, val);
      // Reset the original side, except padding: keeping it only adds a little room on the other side.
      if (!(other in has) && !prop.startsWith('padding')) out.set(prop, prop.startsWith('border') ? (prop.endsWith('color') ? 'transparent' : prop.endsWith('width') ? '0' : 'none') : '0');
    } else if (prop === 'text-align' && /^(left|right)$/.test(val)) {
      out.set(prop, val === 'left' ? 'right' : 'left');
    } else if (/transform/.test(prop) && /translateX\(/.test(val)) {
      out.set(prop, val.replace(/translateX\((-?)([^)]+)\)/g, (m, neg, v) => `translateX(${neg ? '' : '-'}${v})`));
    } else if (prop === 'background-position' && /^(right|left)\b/.test(val)) {
      out.set(prop, val.replace(/^(left|right)/, (m) => (m === 'left' ? 'right' : 'left')));
    } else if (prop === 'background' && /\bno-repeat (right|left) /.test(val)) {
      out.set('background-position', val.match(/no-repeat (right|left) ([^;]+)$/).slice(1).join(' ').replace(/^(left|right)/, (m) => (m === 'left' ? 'right' : 'left')));
    }
  }
  return [...out].map(([k, v]) => `  ${k}: ${v};`);
}

/* ---------- Dark mode ---------- */
const WHITE = 'var(--c-white)';
function darkDecls(decls) {
  const d = Object.fromEntries(decls);
  const bg = d.background ?? d['background-color'] ?? d['--btn-bg'] ?? '';
  const onInk = /var\(--c-charcoal\)|var\(--c-text\)/.test(bg); // inverted with the theme: keep as is
  const onAccent = /var\(--c-bronze/.test(bg);
  const onLight = /rgba\(255, ?255, ?255, ?0\.(9|8)|(^|\s)white\b|#fff\b/.test(bg);
  const out = [];
  for (const [prop, val] of decls) {
    if ((prop === 'color' || prop === '--btn-fg' || prop === 'fill' || prop === 'stroke') && val.startsWith(WHITE)) {
      if (onInk) continue;
      out.push(`  ${prop}: ${onAccent ? 'var(--d-on-accent)' : '#ffffff'}${val.includes('!important') ? ' !important' : ''};`);
    }
    if (prop === 'color' && /^#(444|444444|555|555555|3a352f)$/i.test(val)) {
      out.push('  color: var(--c-text);'); // hard-coded dark text
    }
    if ((prop === 'color' || prop === '--btn-fg') && /var\(--c-(charcoal|text)\)/.test(val) && onLight) {
      out.push(`  ${prop}: #222222;`);
    }
  }
  return out;
}

/* ---------- Build ---------- */
const rtl = [];
const dark = [];
for (const f of FILES) {
  for (const r of parse(readFileSync(`${DIR}/${f}.css`, 'utf8'))) {
    const rd = rtlDecls(r.decls);
    if (rd.length) rtl.push(wrap(r.media, `${prefix(r.selectors, '[dir="rtl"]')} {\n${rd.join('\n')}\n}`));
    const dd = darkDecls(r.decls);
    if (dd.length) dark.push(wrap(r.media, `${prefix(r.selectors, '[data-theme="sombre"]')} {\n${dd.join('\n')}\n}`));
  }
}

const css = `/* =========================================================
   Arabic (right to left) and dark mode — GENERATED by scripts/build-modes.mjs.
   Do not edit: change the source stylesheets or the generator.
   ========================================================= */

/* ---------- Dark palette (the generator derives it from the client's accent) ---------- */
:root[data-theme="sombre"] {
  --d-bg: #171411;
  --d-surface: #211d19;
  --d-alt: #1c1915;
  --d-text: #ede8e0;
  --d-muted: #b3ab9f;
  --d-accent: #d9c3a1;
  --d-on-accent: #171411;
  --c-ivory: var(--d-bg);
  --c-bg: var(--d-bg);
  --c-white: var(--d-surface);
  --c-surface: var(--d-surface);
  --c-beige: var(--d-alt);
  --c-charcoal: var(--d-text);
  --c-text: var(--d-text);
  --c-gray: var(--d-muted);
  --c-muted: var(--d-muted);
  --c-bronze: var(--d-accent);
  --c-bronze-dark: var(--d-accent);
  --c-bronze-light: var(--d-accent);
  --c-line: rgba(237, 232, 224, 0.12);
  --c-line-strong: rgba(237, 232, 224, 0.22);
  --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.3);
  --shadow-md: 0 10px 30px -12px rgba(0, 0, 0, 0.6);
  --shadow-lg: 0 24px 60px -24px rgba(0, 0, 0, 0.7);
  color-scheme: dark;
}
[data-theme="sombre"] .site-header.is-solid { background: color-mix(in srgb, var(--d-bg) 94%, transparent); }
[data-theme="sombre"] img { filter: brightness(0.9); }
[data-theme="sombre"] .detail__mobile-bar { background: color-mix(in srgb, var(--d-bg) 96%, transparent); }
[data-theme="sombre"] .fav-btn.fav-btn--outline { color: var(--c-text); border-color: var(--c-line-strong); }
[data-theme="sombre"] .logo:not(.logo--light) { --logo-accent: var(--d-accent); }

${dark.join('\n')}

/* ---------- Light / dark switch and language button (header) ---------- */
.header-theme__sun { display: none; }
[data-theme="sombre"] .header-theme__sun { display: block; }
[data-theme="sombre"] .header-theme__moon { display: none; }
.header-lang { font-weight: 600; font-size: 1rem; text-decoration: none; }

/* ---------- Arabic: font, no letter-spacing (it breaks joined letters), mirrored layout ---------- */
[dir="rtl"] {
  --f-sans: 'IBM Plex Sans Arabic', 'Manrope', system-ui, sans-serif;
  --f-serif: 'IBM Plex Sans Arabic', 'Manrope', system-ui, sans-serif;
}
[dir="rtl"] body, [dir="rtl"] body * { letter-spacing: 0 !important; }
[dir="rtl"] .rtl-flip { transform: scaleX(-1); }
[dir="rtl"] input[dir="ltr"] { text-align: right; }

${rtl.join('\n')}
`;
writeFileSync(`${DIR}/modes.css`, css);
console.log(`modes.css: ${rtl.length} right-to-left rules, ${dark.length} dark-mode rules`);
