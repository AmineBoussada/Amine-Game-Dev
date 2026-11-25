#!/usr/bin/env node
/**
 * Generates a rebranded real-estate WordPress theme for a new agency from the
 * tested NOVA IMMOBILIER theme (wordpress/nova-immobilier).
 *
 * Usage (from the repository root):
 *   node .claude/skills/real-estate-wp-theme/scripts/generate-theme.mjs <config.json> [--force] [--no-zip]
 *
 * Output: wordpress/<slug>/ (theme folder) and wordpress/<slug>.zip (upload in WordPress).
 *
 * `layout` in the config chooses the front-end: "nova" (default, the NOVA design) or "riviera"
 * (layouts/riviera: navy and white, light/dark switch, French/Arabic with right-to-left display).
 */
import { cpSync, existsSync, readFileSync, readdirSync, rmSync, statSync, writeFileSync } from 'node:fs';
import { execSync } from 'node:child_process';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const SOURCE = 'wordpress/nova-immobilier';
const LAYOUTS = join(dirname(fileURLToPath(import.meta.url)), '..', 'layouts');
const TEXT_EXT = /\.(php|css|js|json|svg|txt|md)$/i;

/* ---------------- Config ---------------- */
const [configPath] = process.argv.slice(2).filter((a) => !a.startsWith('--'));
const FORCE = process.argv.includes('--force');
const NO_ZIP = process.argv.includes('--no-zip');
if (!configPath) fail('Usage: generate-theme.mjs <config.json> [--force] [--no-zip]');
if (!existsSync(SOURCE)) fail(`Source theme not found: ${SOURCE} (run from the repository root).`);

const cfg = JSON.parse(readFileSync(configPath, 'utf8'));
const errors = [];
const req = (path) => {
  const v = path.split('.').reduce((o, k) => (o == null ? o : o[k]), cfg);
  if (v === undefined || v === null || v === '') errors.push(`missing "${path}"`);
  return v;
};
['name', 'shortName', 'slug', 'prefix', 'refPrefix', 'colors.background', 'colors.surface', 'colors.text', 'colors.muted',
  'colors.accent', 'colors.soft', 'locality.wilaya', 'locality.a', 'locality.de', 'locality.adjective',
  'contact.phone', 'contact.whatsapp', 'contact.email', 'contact.city'].forEach(req);
if (cfg.slug && !/^[a-z0-9]+(-[a-z0-9]+)*$/.test(cfg.slug)) errors.push('"slug" must be lowercase letters, digits and hyphens');
if (cfg.slug === 'nova-immobilier') errors.push('"slug" must differ from the source theme');
if (cfg.prefix && !/^[a-z][a-z0-9]{1,9}$/.test(cfg.prefix)) errors.push('"prefix" must be 2–10 lowercase letters/digits, starting with a letter');
if (cfg.prefix === 'nova' || cfg.prefix === 'wp') errors.push('"prefix" cannot be "nova" or "wp"');
if (cfg.refPrefix && !/^[A-Z0-9]{2,8}$/.test(cfg.refPrefix)) errors.push('"refPrefix" must be 2–8 uppercase letters/digits');
for (const [k, v] of Object.entries(cfg.colors ?? {})) if (!/^#[0-9a-fA-F]{6}$/.test(v)) errors.push(`colors.${k} must be a #RRGGBB hex`);
const LAYOUT = cfg.layout ?? 'nova';
if (LAYOUT !== 'nova' && !existsSync(join(LAYOUTS, LAYOUT))) errors.push(`unknown "layout": ${LAYOUT} (available: nova, ${readdirSync(LAYOUTS).join(', ')})`);
if (LAYOUT === 'riviera') ['nameAr', 'locality.cityAr'].forEach(req);
if (errors.length) fail('Invalid config:\n  - ' + errors.join('\n  - '));

const P = cfg.prefix;
const PU = P.toUpperCase();
const Pascal = P[0].toUpperCase() + P.slice(1);
const name = cfg.name;
const short = cfg.shortName;
const logoBottom = cfg.logoBottom ?? 'IMMOBILIER';
const initials = name.split(/\s+/).filter(Boolean).slice(0, 2).map((w) => w[0].toUpperCase()).join('');
const domain = cfg.domain ?? `${cfg.slug}.dz`;
const target = `wordpress/${cfg.slug}`;

/* ---------------- Colors ---------------- */
const hex2rgb = (h) => [1, 3, 5].map((i) => parseInt(h.slice(i, i + 2), 16));
const rgb2hex = (r) => '#' + r.map((v) => Math.max(0, Math.min(255, Math.round(v))).toString(16).padStart(2, '0')).join('');
const mix = (a, b, t) => rgb2hex(hex2rgb(a).map((v, i) => v + (hex2rgb(b)[i] - v) * t));
const c = cfg.colors;
const accentRgb = hex2rgb(c.accent).join(', ');
const derived = {
  accentDark: mix(c.accent, '#000000', 0.17),
  accentLight: mix(c.accent, '#ffffff', 0.45),
  accentPale: mix(c.accent, '#ffffff', 0.2),
  line: mix(c.soft, c.text, 0.04),
  lineStrong: mix(c.soft, c.text, 0.1),
  mutedText: mix(c.muted, c.text, 0.2),
  footer: mix(c.text, '#000000', 0.2),
};

/* ---------------- Copy ---------------- */
if (existsSync(target)) {
  if (!FORCE) fail(`${target} already exists. Re-run with --force to overwrite.`);
  rmSync(target, { recursive: true, force: true });
}
cpSync(SOURCE, target, { recursive: true });
const zipLeft = join(target, `${SOURCE.split('/').pop()}.zip`);
if (existsSync(zipLeft)) rmSync(zipLeft);
if (LAYOUT !== 'nova') {
  // The layout replaces the front-end templates and styles; the back office stays the base theme's.
  for (const f of ['tokens', 'base', 'components', 'pages', 'theme']) rmSync(join(target, `assets/css/${f}.css`), { force: true });
  for (const f of ['cta-banner', 'stats']) rmSync(join(target, `template-parts/${f}.php`), { force: true });
  cpSync(join(LAYOUTS, LAYOUT), target, { recursive: true });
}

/* ---------------- Helpers ---------------- */
const phpStr = (s) =>
  String(s).includes('\n')
    ? '"' + String(s).replace(/\\/g, '\\\\').replace(/"/g, '\\"').replace(/\$/g, '\\$').replace(/\n/g, '\\n') + '"'
    : "'" + String(s).replace(/\\/g, '\\\\').replace(/'/g, "\\'") + "'";
const esc = (s) => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
const walk = (dir) =>
  readdirSync(dir).flatMap((f) => {
    const p = join(dir, f);
    return statSync(p).isDirectory() ? walk(p) : [p];
  });

/* ---------------- Replacement rules (order matters) ---------------- */
const cityName = cfg.locality.city ?? cfg.locality.wilaya;
const rules = [
  // Placeholders used by alternative layouts.
  [/__SHORT__/g, short],
  [/__NAME_AR__/g, cfg.nameAr ?? name],
  [/__CITY_AR__/g, cfg.locality.cityAr ?? cityName],
  [/__CITY__/g, cityName],
  [/__LOGO_BOTTOM_AR__/g, cfg.logoBottomAr ?? 'عقارات'],
  [/__LOGO_BOTTOM__/g, logoBottom],
  // Brand text first (before identifier rules touch "nova").
  [/NOVA IMMOBILIER/g, name],
  [/<span class="logo__name">NOVA<\/span><span class="logo__sub">IMMOBILIER<\/span>/g,
    `<span class="logo__name">${short}</span><span class="logo__sub">${logoBottom}</span>`],
  [/<path d="M12 29V11l16 18V11"[^>]*\/>/g,
    `<text x="20" y="28" text-anchor="middle" font-size="21" style="fill:var(--logo-accent);font-family:var(--f-serif);font-weight:600">${short[0]}</text>`],
  [/Votre conseiller NOVA/g, `Votre conseiller ${short}`],
  [/\[NOVA\]/g, `[${short}]`],
  [/>NI</g, `>${initials}<`],
  [/NOVA-(?=['\d])/g, `${cfg.refPrefix}-`],
  [/nova-immobilier\.dz/g, domain],
  // Locality.
  [/gence immobilière à Alger/g, `gence immobilière ${cfg.locality.a}`],
  [/basée à Alger/g, `basée ${cfg.locality.a}`],
  [/quartiers d’Alger/g, `quartiers ${cfg.locality.de}`],
  [/Une agence algéroise/g, `Une agence ${cfg.locality.adjective}`],
  [/'default' => 'Alger'/g, `'default' => ${phpStr(cfg.locality.wilaya)}`],
  ...(cfg.locality.communesExample ? [[/'Hydra, Kouba, Cheraga…'/g, phpStr(cfg.locality.communesExample)]] : []),
  // Code identifiers.
  [/NovaTheme/g, `${Pascal}Theme`],
  [/nova:favorites/g, `${P}:favorites`],
  [/\bNOVA_/g, `${PU}_`],
  [/(?<![A-Za-z0-9])nova_/g, `${P}_`],
  [/(?<![A-Za-z0-9])nova-/g, `${P}-`],
  [/(?<![A-Za-z0-9])nova\[/g, `${P}[`],
  [/'nova'/g, `'${P}'`],
  [/Text Domain: nova\b/g, `Text Domain: ${P}`],
  [/(?<![A-Za-z0-9])nova seed/g, `${P} seed`],
  // Colors (hard-coded uses of the accent and its variants).
  [/#a8875a/gi, c.accent],
  [/rgba\(168, ?135, ?90/g, `rgba(${accentRgb}`],
  [/#8c6f47/gi, derived.accentDark],
  [/#d9c3a1/gi, derived.accentLight],
  [/#c2a27a/gi, derived.accentPale],
  [/#1b1b1b/gi, derived.footer],
  [/#F7F5F0/g, c.background],
];

let changed = 0;
for (const file of walk(target)) {
  if (!TEXT_EXT.test(file)) continue;
  const before = readFileSync(file, 'utf8');
  let s = before;
  for (const [re, to] of rules) s = s.replace(re, to);
  if (s !== before) {
    writeFileSync(file, s);
    changed++;
  }
}

/* ---------------- Design tokens ---------------- */
const fonts = cfg.fonts ?? {};
if (LAYOUT === 'nova') {
  const tokensPath = join(target, 'assets/css/tokens.css');
  let tokens = readFileSync(tokensPath, 'utf8');
  const setToken = (tok, val) => {
    const re = new RegExp(`(${esc(tok)}:\\s*)[^;]+;`);
    if (!re.test(tokens)) fail(`Token ${tok} not found in tokens.css`);
    tokens = tokens.replace(re, `$1${val};`);
  };
  setToken('--c-ivory', c.background);
  setToken('--c-white', c.surface);
  setToken('--c-charcoal', c.text);
  setToken('--c-gray', c.muted);
  setToken('--c-bronze', c.accent);
  setToken('--c-beige', c.soft);
  setToken('--c-bronze-dark', derived.accentDark);
  setToken('--c-bronze-soft', `rgba(${accentRgb}, 0.12)`);
  setToken('--c-bronze-light', derived.accentLight);
  setToken('--c-line', derived.line);
  setToken('--c-line-strong', derived.lineStrong);
  setToken('--c-muted', derived.mutedText);
  if (fonts.display) setToken('--f-serif', `'${fonts.display}', Georgia, 'Times New Roman', serif`);
  if (fonts.body) setToken('--f-sans', `'${fonts.body}', system-ui, -apple-system, 'Segoe UI', sans-serif`);
  writeFileSync(tokensPath, tokens);

  // Google Fonts URL.
  if (fonts.display || fonts.body) {
    const fam = (f, w) => `family=${f.replace(/ /g, '+')}:wght@${w}`;
    const url = `https://fonts.googleapis.com/css2?${fam(fonts.body ?? 'Manrope', fonts.bodyWeights ?? '400;500;600;700')}&${fam(fonts.display ?? 'Playfair Display', fonts.displayWeights ?? '500;600')}&display=swap`;
    const fp = join(target, 'functions.php');
    const s = readFileSync(fp, 'utf8').replace(/'https:\/\/fonts\.googleapis\.com\/css2\?[^']+'/, `'${url}'`);
    writeFileSync(fp, s);
  }
} else {
  setLayoutTokens();
}

/* ---------------- Settings defaults ---------------- */
const settingsPath = join(target, 'inc/settings.php');
let settings = readFileSync(settingsPath, 'utf8');
const t = cfg.texts ?? {};
const ct = cfg.contact;
const so = cfg.socials ?? {};
const defaults = {
  phone: ct.phone,
  mobile: ct.mobile ?? ct.phone,
  whatsapp: String(ct.whatsapp).replace(/\D+/g, ''),
  email: ct.email,
  address: ct.address ?? '',
  city: ct.city,
  hours: (ct.hours ?? [['Dimanche – Jeudi', '08h30 – 17h30'], ['Samedi', '09h00 – 13h00'], ['Vendredi', 'Fermé']]).map((h) => h.join(' | ')).join('\n'),
  facebook: so.facebook ?? '',
  instagram: so.instagram ?? '',
  linkedin: so.linkedin ?? '',
  youtube: so.youtube ?? '',
  ...(t.description && { description: t.description }),
  ...(t.heroTitle && { hero_title: t.heroTitle }),
  ...(t.heroText && { hero_text: t.heroText }),
  ...(t.aboutTitle && { about_title: t.aboutTitle }),
  ...(t.aboutText && { about_text: t.aboutText }),
  demo_notice: cfg.demoNotice === false ? 0 : 1,
};
(t.stats ?? []).slice(0, 3).forEach(([v, l], i) => {
  defaults[`stat${i + 1}_value`] = v;
  defaults[`stat${i + 1}_label`] = l;
});
for (const [key, value] of Object.entries(defaults)) {
  const re = new RegExp(`('${key}' => )(?:'(?:[^'\\\\]|\\\\.)*'|"(?:[^"\\\\]|\\\\.)*"|\\d+)`);
  if (!re.test(settings)) fail(`Setting default "${key}" not found in inc/settings.php`);
  settings = settings.replace(re, `$1${typeof value === 'number' ? value : phpStr(value)}`);
}
writeFileSync(settingsPath, settings);

if (LAYOUT === 'riviera') {
  // Arabic texts of the settings page (defaults in inc/layout.php).
  const layoutPath = join(target, 'inc/layout.php');
  let layoutPhp = readFileSync(layoutPath, 'utf8');
  const ta = cfg.textsAr ?? {};
  const arDefaults = {
    ...(ta.heroTitle && { hero_title_ar: ta.heroTitle }),
    ...(ta.heroText && { hero_text_ar: ta.heroText }),
    ...(ta.aboutTitle && { about_title_ar: ta.aboutTitle }),
    ...(ta.aboutText && { about_text_ar: ta.aboutText }),
    ...(ta.description && { description_ar: ta.description }),
    ...(ta.address && { address_ar: ta.address }),
    ...(ta.city && { city_ar: ta.city }),
  };
  (ta.stats ?? []).slice(0, 3).forEach((l, i) => (arDefaults[`stat${i + 1}_label_ar`] = l));
  for (const [key, value] of Object.entries(arDefaults)) {
    const re = new RegExp(`('${key}' => )'(?:[^'\\\\]|\\\\.)*'`);
    if (!re.test(layoutPhp)) fail(`Arabic setting default "${key}" not found in inc/layout.php`);
    layoutPhp = layoutPhp.replace(re, `$1${phpStr(value)}`);
  }
  writeFileSync(layoutPath, layoutPhp);
}

/* ---------------- Demo content ---------------- */
if (cfg.demoContent === false) writeFileSync(join(target, 'inc/seed-data.json'), '[]\n');

/* ---------------- Theme header ---------------- */
const stylePath = join(target, 'style.css');
let style = readFileSync(stylePath, 'utf8')
  .replace(/Theme URI: .*/, `Theme URI: https://${domain}`)
  .replace(/Version: .*/, `Version: ${cfg.version ?? '1.0.0'}`);
writeFileSync(stylePath, style);

/* ---------------- Report ---------------- */
const leftovers = walk(target)
  .filter((f) => TEXT_EXT.test(f))
  .flatMap((f) =>
    readFileSync(f, 'utf8')
      .split('\n')
      .map((line, i) => [f, i + 1, line])
      .filter(([, , line]) => /(?<![a-z])nova(?!lidate)/i.test(line)),
  );
console.log(`✓ Theme generated: ${target}  (${changed} files rebranded)`);
console.log(`  name: ${name} · layout: ${LAYOUT} · prefix: ${P}_ · references: ${cfg.refPrefix}-xxxx · WP-CLI: wp ${P} seed`);
if (leftovers.length) {
  console.log(`✗ ${leftovers.length} leftover "nova" occurrence(s):`);
  leftovers.slice(0, 20).forEach(([f, n, l]) => console.log(`  ${f}:${n}: ${l.trim().slice(0, 140)}`));
  process.exitCode = 1;
}

if (!NO_ZIP) {
  try {
    execSync(`cd wordpress && rm -f ${cfg.slug}.zip && zip -qr ${cfg.slug}.zip ${cfg.slug} -x '*.DS_Store'`);
    console.log(`✓ Zip: ${resolve(`wordpress/${cfg.slug}.zip`)}`);
  } catch {
    console.log('! "zip" command not available: compress the theme folder manually.');
  }
}

/** Colours of an alternative layout: light tokens from the config, dark tokens derived from the accent. */
function setLayoutTokens() {
  const cssPath = join(target, `assets/css/${LAYOUT}.css`);
  let css = readFileSync(cssPath, 'utf8');
  const set = (block, tok, val) => {
    const re = new RegExp(`(${block}[^}]*?${esc(tok)}:\\s*)[^;]+;`);
    if (!re.test(css)) fail(`Token ${tok} not found in ${cssPath}`);
    css = css.replace(re, `$1${val};`);
  };
  const light = ':root \\{';
  set(light, '--bg', c.background);
  set(light, '--surface', c.surface);
  set(light, '--ink', c.text);
  set(light, '--muted', c.muted);
  set(light, '--marine', c.accent);
  set(light, '--alt', c.soft);
  if (c.gold) set(light, '--gold', c.gold);
  if (c.accent.toUpperCase() !== '#0E2A47') {
    const dark = ':root\\[data-theme="sombre"\\] \\{';
    const bg = mix(c.accent, '#000000', 0.4);
    set(dark, '--bg', bg);
    set(dark, '--surface', mix(c.accent, '#000000', 0.2));
    set(dark, '--alt', mix(c.accent, '#000000', 0.28));
    set(dark, '--band', mix(c.accent, '#000000', 0.55));
    set(dark, '--btn-ink', bg);
    set(dark, '--stripe-bg', bg);
    set(dark, '--header', `rgba(${hex2rgb(bg).join(', ')}, 0.97)`);
    set(dark, '--footer', mix(c.accent, '#000000', 0.68));
  }
  writeFileSync(cssPath, css);
  if (cfg.fonts) console.log(`! "fonts" is ignored by the ${LAYOUT} layout (Jost, and IBM Plex Sans Arabic in Arabic).`);
}

function fail(msg) {
  console.error('✗ ' + msg);
  process.exit(1);
}
