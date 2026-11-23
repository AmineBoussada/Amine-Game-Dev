/**
 * Generated architectural illustrations (SVG data URIs) used as default visuals
 * when a property photo is unavailable. Deterministic: the same seed always
 * produces the same picture, so each image keeps its own look.
 */
export type Scene =
  | 'villa'
  | 'villa-pool'
  | 'building'
  | 'living'
  | 'kitchen'
  | 'bedroom'
  | 'bathroom'
  | 'dining'
  | 'land'
  | 'shop'
  | 'office';

type Rng = () => number;

function hash(str: string) {
  let h = 2166136261;
  for (let i = 0; i < str.length; i++) {
    h ^= str.charCodeAt(i);
    h = Math.imul(h, 16777619);
  }
  return h >>> 0;
}

function mulberry32(seed: number): Rng {
  let a = seed;
  return () => {
    a |= 0;
    a = (a + 0x6d2b79f5) | 0;
    let t = Math.imul(a ^ (a >>> 15), 1 | a);
    t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
    return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
  };
}

const pick = <T,>(r: Rng, arr: readonly T[]) => arr[Math.floor(r() * arr.length)];
const between = (r: Rng, min: number, max: number) => Math.round(min + r() * (max - min));

/* ---------- Palette (brand-aligned) ---------- */
const skies = [
  ['#E7D9C3', '#F7F1E7'],
  ['#DDB993', '#F4E3CC'],
  ['#C8D2D3', '#F0F0EA'],
  ['#C6AE92', '#EEDDC7'],
] as const;
const walls = ['#EFE8DD', '#E8DFD2', '#F3EEE6', '#E4D8C8', '#EAE4DA'] as const;
const accents = ['#A8875A', '#9AA38C', '#B5785A', '#6E7579', '#857F5C', '#C2A27A'] as const;
const woods = [
  ['#C9A57E', '#B08660'],
  ['#D6B993', '#BE9C74'],
  ['#A98663', '#8E6D4E'],
] as const;
const GLASS = '#2F3539';
const CHARCOAL = '#2A2826';
const IVORY = '#F8F5EF';
const BRONZE = '#A8875A';

const W = 1200;
const H = 800;

const svg = (defs: string, body: string) =>
  `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${W} ${H}" preserveAspectRatio="xMidYMid slice"><defs>${defs}</defs>${body}</svg>`;

const lg = (id: string, c1: string, c2: string, vertical = true) =>
  `<linearGradient id="${id}" x1="0" y1="0" x2="${vertical ? 0 : 1}" y2="${vertical ? 1 : 0}"><stop offset="0" stop-color="${c1}"/><stop offset="1" stop-color="${c2}"/></linearGradient>`;

const rect = (x: number, y: number, w: number, h: number, fill: string, extra = '') =>
  `<rect x="${x}" y="${y}" width="${w}" height="${h}" fill="${fill}" ${extra}/>`;

/* ---------- Shared pieces ---------- */
function skyAndHills(r: Rng, horizon: number) {
  const [top, bottom] = pick(r, skies);
  const sunX = between(r, 760, 1040);
  const h1 = horizon - between(r, 60, 110);
  const h2 = horizon - between(r, 20, 60);
  return {
    defs: lg('sky', top, bottom),
    body:
      rect(0, 0, W, H, 'url(#sky)') +
      `<circle cx="${sunX}" cy="${between(r, 140, 210)}" r="${between(r, 50, 80)}" fill="#FBF1E1" opacity=".85"/>` +
      `<path d="M0 ${horizon} L0 ${h1 + 30} C180 ${h1 - 20} 330 ${h1 + 10} 480 ${h1 - 10} S800 ${h1 - 40} 960 ${h1} S1140 ${h1 + 20} 1200 ${h1} L1200 ${horizon} Z" fill="#CBBCA3" opacity=".6"/>` +
      `<path d="M0 ${horizon} L0 ${h2 + 10} C220 ${h2 - 30} 420 ${h2 + 20} 640 ${h2} S1000 ${h2 - 20} 1200 ${h2 + 10} L1200 ${horizon} Z" fill="#B9A88C" opacity=".55"/>`,
  };
}

function olive(x: number, ground: number, s = 1, color = '#8C9672') {
  return (
    rect(x - 6 * s, ground - 90 * s, 12 * s, 90 * s, '#6B5A48') +
    `<ellipse cx="${x}" cy="${ground - 120 * s}" rx="${70 * s}" ry="${50 * s}" fill="${color}"/>` +
    `<ellipse cx="${x - 40 * s}" cy="${ground - 100 * s}" rx="${40 * s}" ry="${30 * s}" fill="${color}" opacity=".9"/>` +
    `<ellipse cx="${x + 38 * s}" cy="${ground - 104 * s}" rx="${42 * s}" ry="${30 * s}" fill="${color}" opacity=".85"/>`
  );
}

function cypress(x: number, ground: number, h = 220, color = '#6F7A5C') {
  return rect(x - 4, ground - 20, 8, 20, '#5E4E3E') + `<ellipse cx="${x}" cy="${ground - h / 2 - 10}" rx="${h * 0.14}" ry="${h / 2}" fill="${color}"/>`;
}

function plant(x: number, base: number, s = 1, pot = '#D9CFC0', leaf = '#7F8A66') {
  const leaves = [-50, -25, 0, 25, 50]
    .map(
      (a, i) =>
        `<ellipse cx="${x}" cy="${base - 70 * s - (i % 2) * 14 * s}" rx="${16 * s}" ry="${58 * s}" fill="${leaf}" opacity="${0.8 + (i % 2) * 0.2}" transform="rotate(${a} ${x} ${base - 40 * s})"/>`,
    )
    .join('');
  return leaves + `<path d="M${x - 34 * s} ${base - 56 * s} h${68 * s} l-${8 * s} ${56 * s} h-${52 * s} Z" fill="${pot}"/>`;
}

function windowView(r: Rng, x: number, y: number, w: number, h: number, id: string) {
  const [top, bottom] = pick(r, skies);
  const hy = y + h * 0.68;
  return {
    defs: lg(id, top, bottom),
    body:
      rect(x, y, w, h, `url(#${id})`) +
      `<path d="M${x} ${hy} C${x + w * 0.3} ${hy - 40} ${x + w * 0.6} ${hy - 10} ${x + w} ${hy - 30} L${x + w} ${y + h} L${x} ${y + h} Z" fill="#BDAF96" opacity=".7"/>` +
      rect(x, y + h * 0.82, w, h * 0.18, '#A8B5B0', 'opacity=".55"') +
      `<rect x="${x}" y="${y}" width="${w}" height="${h}" fill="none" stroke="${CHARCOAL}" stroke-width="10"/>` +
      `<line x1="${x + w / 2}" y1="${y}" x2="${x + w / 2}" y2="${y + h}" stroke="${CHARCOAL}" stroke-width="6"/>`,
  };
}

function artwork(r: Rng, x: number, y: number, w: number, h: number) {
  const a = pick(r, accents);
  const b = pick(r, accents);
  return (
    rect(x - 8, y - 8, w + 16, h + 16, '#3A3531') +
    rect(x, y, w, h, '#F6F1E8') +
    `<circle cx="${x + w * 0.38}" cy="${y + h * 0.48}" r="${h * 0.26}" fill="${a}" opacity=".85"/>` +
    rect(x + w * 0.5, y + h * 0.3, w * 0.32, h * 0.44, b, 'opacity=".7"') +
    `<line x1="${x + w * 0.12}" y1="${y + h * 0.82}" x2="${x + w * 0.88}" y2="${y + h * 0.82}" stroke="${CHARCOAL}" stroke-width="2" opacity=".5"/>`
  );
}

function woodFloor(r: Rng, y: number) {
  const [c1, c2] = pick(r, woods);
  let lines = '';
  for (let i = 1; i < 8; i++) {
    const ly = y + ((H - y) * i * i) / 64;
    lines += `<line x1="0" y1="${ly}" x2="${W}" y2="${ly}" stroke="#000" stroke-opacity=".06" stroke-width="2"/>`;
  }
  return { defs: lg('floor', c1, c2), body: rect(0, y, W, H - y, 'url(#floor)') + lines };
}

function pendant(x: number, top: number, len: number, color = CHARCOAL) {
  return (
    `<line x1="${x}" y1="${top}" x2="${x}" y2="${top + len}" stroke="${CHARCOAL}" stroke-width="2"/>` +
    `<path d="M${x - 36} ${top + len + 34} Q${x} ${top + len - 18} ${x + 36} ${top + len + 34} Z" fill="${color}"/>` +
    `<ellipse cx="${x}" cy="${top + len + 40}" rx="30" ry="9" fill="#FFE9C2" opacity=".85"/>`
  );
}

/* ---------- Scenes ---------- */
function villa(r: Rng, pool: boolean) {
  const ground = 590;
  const sky = skyAndHills(r, 520);
  const clad = pick(r, ['#B38B63', '#A07B57', '#C39A6E']);
  const bx = between(r, 220, 300);
  let body = sky.body;
  body += rect(0, 510, W, H - 510, '#D8CCB6');
  body += rect(0, 540, W, 60, '#A7AD8C', 'opacity=".75"');
  body += cypress(between(r, 90, 140), ground, between(r, 220, 280));
  body += olive(between(r, 1020, 1110), ground, 1.05);
  // lower volume
  body += rect(bx, 380, 640, 210, IVORY);
  body += rect(bx, 380, 640, 14, '#E1D7C7');
  body += rect(bx + 40, 420, 270, 150, 'url(#glass)');
  for (let i = 1; i < 4; i++) body += rect(bx + 40 + i * 67, 420, 4, 150, BRONZE);
  body += rect(bx + 350, 400, 120, 190, clad);
  for (let i = 1; i < 6; i++) body += rect(bx + 350 + i * 20, 400, 2, 190, '#000', 'opacity=".12"');
  body += rect(bx + 500, 430, 110, 160, 'url(#glass)');
  // upper cantilevered volume
  body += rect(bx + 150, 400, 560, 16, '#000', 'opacity=".08"');
  body += rect(bx + 150, 250, 560, 150, '#FFFFFF');
  body += rect(bx + 150, 250, 560, 14, '#E1D7C7');
  body += rect(bx + 190, 288, 400, 78, 'url(#glass)');
  for (let i = 1; i < 5; i++) body += rect(bx + 190 + i * 80, 288, 3, 78, BRONZE);
  body += rect(bx + 620, 288, 60, 78, clad);
  if (pool) {
    body += rect(0, 600, W, 200, '#E9E0D0');
    body += rect(140, 640, 760, 110, 'url(#pool)');
    for (let i = 0; i < 6; i++)
      body += `<path d="M${180 + i * 120} ${672 + (i % 2) * 22} q30 -10 60 0" stroke="#fff" stroke-opacity=".55" stroke-width="3" fill="none"/>`;
    body += rect(140, 636, 760, 6, '#F6F1E8');
  } else {
    body += rect(0, 600, W, 200, '#D2C4AC');
    body += `<path d="M${bx + 380} 600 L${bx + 300} 800 L${bx + 480} 800 L${bx + 430} 600 Z" fill="#E6DCCB"/>`;
  }
  body += plant(bx - 40, 600, 0.9);
  return svg(sky.defs + lg('glass', '#4A535A', GLASS) + lg('pool', '#8FC0C2', '#4F8D94'), body);
}

function building(r: Rng) {
  const sky = skyAndHills(r, 540);
  const floors = between(r, 6, 8);
  const tx = between(r, 300, 380);
  const fh = 440 / floors;
  const facade = pick(r, ['#F4EFE7', '#EFE7DA', '#F7F3EC']);
  let body = sky.body;
  // distant city
  for (let i = 0; i < 9; i++) body += rect(i * 140 - 20, 540 - between(r, 60, 150), between(r, 70, 120), 200, '#BFB29C', 'opacity=".45"');
  body += rect(0, 600, W, 200, '#D8CCB6');
  body += rect(tx, 160, 500, 450, facade);
  body += rect(tx, 150, 500, 14, '#DED3C2');
  for (let f = 0; f < floors; f++) {
    const y = 175 + f * fh;
    for (let c = 0; c < 4; c++) body += rect(tx + 30 + c * 118, y + 8, 84, fh - 26, 'url(#glass)');
    body += rect(tx - 14, y + fh - 14, 528, 9, '#E2D8C8');
    body += rect(tx - 14, y + fh - 36, 528, 22, '#FFFFFF', 'opacity=".28"');
  }
  for (let c = 0; c <= 4; c++) body += rect(tx + 14 + c * 118, 165, 6, 445, BRONZE, 'opacity=".55"');
  body += rect(tx + 500, 320, 200, 290, '#E8E0D3');
  for (let f = 0; f < 4; f++) for (let c = 0; c < 2; c++) body += rect(tx + 530 + c * 80, 345 + f * 64, 56, 40, 'url(#glass)');
  body += rect(0, 610, W, 14, '#C9BBA2');
  body += olive(between(r, 120, 200), 640, 0.9) + olive(between(r, 1000, 1100), 650, 1);
  body += rect(0, 700, W, 100, '#BEB09A');
  return svg(sky.defs + lg('glass', '#56606A', GLASS), body);
}

function living(r: Rng) {
  const wall = pick(r, walls);
  const floor = woodFloor(r, 560);
  const view = windowView(r, 650, 100, 450, 410, 'view');
  const sofa = pick(r, ['#D8CDBD', '#B9AE9B', '#9AA38C', '#C9B39A', '#8F8579']);
  let body = rect(0, 0, W, 560, wall) + floor.body + view.body;
  body += rect(600, 80, 46, 460, '#FFFFFF', 'opacity=".45"');
  body += artwork(r, 170, 160, 230, 160);
  body += `<ellipse cx="380" cy="660" rx="330" ry="56" fill="#EDE5D7" opacity=".9"/>`;
  body += rect(110, 395, 480, 95, sofa, 'rx="26"');
  body += rect(110, 450, 480, 110, sofa, 'rx="18"');
  body += rect(90, 430, 60, 130, sofa, 'rx="22" style="filter:brightness(.92)"');
  body += rect(550, 430, 60, 130, sofa, 'rx="22"');
  body += rect(150, 435, 190, 40, '#FFFFFF', 'rx="16" opacity=".35"');
  body += rect(360, 435, 190, 40, '#FFFFFF', 'rx="16" opacity=".35"');
  body += rect(160, 400, 70, 60, pick(r, accents), 'rx="14" opacity=".85"');
  body += rect(250, 610, 260, 16, '#3A3632') + rect(270, 626, 8, 50, '#3A3632') + rect(482, 626, 8, 50, '#3A3632');
  body += `<ellipse cx="320" cy="598" rx="18" ry="14" fill="${BRONZE}"/>`;
  body += `<path d="M640 720 L640 300 Q640 250 700 250" stroke="${CHARCOAL}" stroke-width="5" fill="none"/>`;
  body += `<path d="M680 250 h60 l-12 40 h-36 Z" fill="${CHARCOAL}"/>`;
  body += plant(1040, 640, 1.2);
  return svg(floor.defs + view.defs, body);
}

function kitchen(r: Rng) {
  const wall = pick(r, walls);
  const cab = pick(r, ['#6E7579', '#9AA38C', '#E9E2D6', '#3E3A36', '#B9A58A']);
  const top = '#EFEAE2';
  const view = windowView(r, 820, 120, 300, 260, 'view');
  let body = rect(0, 0, W, H, wall) + rect(0, 600, W, 200, '#DCD2C3') + view.body;
  for (let i = 0; i < 6; i++) body += rect(i * 200, 600, 2, 200, '#000', 'opacity=".05"');
  body += rect(60, 120, 680, 160, cab);
  for (let i = 1; i < 5; i++) body += rect(60 + i * 136, 120, 3, 160, '#000', 'opacity=".15"');
  body += rect(60, 280, 680, 140, '#F4F0E9');
  for (let i = 0; i < 9; i++) body += rect(60 + i * 76, 280, 1, 140, '#000', 'opacity=".06"');
  body += rect(60, 420, 680, 18, top) + rect(60, 438, 680, 162, cab);
  for (let i = 1; i < 5; i++) body += rect(60 + i * 136, 438, 3, 162, '#000', 'opacity=".15"');
  body += rect(300, 370, 120, 50, '#2E2C2A', 'rx="4"');
  body += rect(260, 520, 640, 20, top) + rect(270, 540, 620, 150, pick(r, ['#3E3A36', '#A98663', '#6E7579']));
  for (let i = 0; i < 3; i++) {
    const sx = 360 + i * 200;
    body += `<ellipse cx="${sx}" cy="610" rx="44" ry="14" fill="${BRONZE}"/>` + rect(sx - 30, 620, 4, 110, CHARCOAL) + rect(sx + 26, 620, 4, 110, CHARCOAL);
  }
  body += pendant(400, 0, 300) + pendant(580, 0, 300) + pendant(760, 0, 300);
  body += `<ellipse cx="620" cy="512" rx="36" ry="12" fill="#B5785A"/>` + plant(1060, 600, 0.9);
  return svg(view.defs, body);
}

function bedroom(r: Rng) {
  const wall = pick(r, walls);
  const floor = woodFloor(r, 580);
  const head = pick(r, ['#C9B39A', '#9AA38C', '#B9AE9B', '#D8C6AE', '#8F8579']);
  const throwC = pick(r, accents);
  const view = windowView(r, 40, 120, 200, 360, 'view');
  let body = rect(0, 0, W, 580, wall) + floor.body + view.body;
  body += rect(20, 90, 60, 470, '#FFFFFF', 'opacity=".5"') + rect(220, 90, 50, 470, '#FFFFFF', 'opacity=".5"');
  body += artwork(r, 500, 130, 200, 130);
  body += rect(330, 290, 540, 210, head, 'rx="18"');
  body += rect(300, 470, 600, 130, '#E4DACB', 'rx="10"');
  body += rect(310, 430, 580, 140, '#FBF8F3', 'rx="18"');
  body += rect(310, 505, 580, 50, throwC, 'rx="10" opacity=".85"');
  body += `<ellipse cx="460" cy="430" rx="95" ry="34" fill="#FFFFFF"/><ellipse cx="740" cy="430" rx="95" ry="34" fill="#FFFFFF"/>`;
  body += `<ellipse cx="600" cy="440" rx="60" ry="24" fill="${pick(r, accents)}" opacity=".7"/>`;
  for (const nx of [190, 920]) {
    body += rect(nx, 470, 90, 110, '#B08660', 'rx="6"');
    body += `<path d="M${nx + 20} 400 h50 l12 46 h-74 Z" fill="#F1E6D4"/>` + rect(nx + 43, 446, 4, 24, CHARCOAL);
    body += `<ellipse cx="${nx + 45}" cy="430" rx="70" ry="50" fill="#FFE9C2" opacity=".25"/>`;
  }
  body += plant(1100, 650, 1);
  return svg(floor.defs + view.defs, body);
}

function bathroom(r: Rng) {
  const tile = pick(r, ['#EEE9E1', '#E3DDD3', '#E8E4DC', '#DCD5CA']);
  let body = rect(0, 0, W, H, tile);
  for (let x = 0; x < W; x += 80) body += rect(x, 0, 1.5, 620, '#000', 'opacity=".06"');
  for (let y = 0; y < 620; y += 80) body += rect(0, y, W, 1.5, '#000', 'opacity=".06"');
  body += rect(0, 620, W, 180, '#CFC6B8');
  body += `<circle cx="370" cy="260" r="130" fill="url(#mirror)" stroke="${BRONZE}" stroke-width="10"/>`;
  body += rect(150, 430, 440, 22, '#F4F0E8') + rect(170, 452, 400, 140, pick(r, ['#A98663', '#6E7579', '#3E3A36', '#9AA38C']), 'rx="4"');
  body += rect(370, 452, 2, 140, '#000', 'opacity=".2"');
  body += `<ellipse cx="370" cy="432" rx="90" ry="16" fill="#FFFFFF"/>` + rect(365, 380, 10, 46, BRONZE) + rect(365, 380, 36, 8, BRONZE);
  body += rect(680, 470, 450, 150, '#FFFFFF', 'rx="70"') + rect(700, 470, 410, 26, '#F0EBE3', 'rx="13"');
  body += rect(1080, 340, 10, 130, BRONZE) + rect(1040, 340, 50, 10, BRONZE);
  const view = windowView(r, 760, 90, 300, 220, 'view');
  body += view.body + plant(110, 640, 1);
  body += rect(170, 600, 160, 12, '#F7F2EA', 'rx="6"');
  return svg(lg('mirror', '#FFFFFF', '#DCD6CC') + view.defs, body);
}

function dining(r: Rng) {
  const wall = pick(r, walls);
  const floor = woodFloor(r, 590);
  const view = windowView(r, 820, 110, 300, 380, 'view');
  const chair = pick(r, ['#3A3632', '#A98663', '#9AA38C', '#C9B39A']);
  let body = rect(0, 0, W, 590, wall) + floor.body + view.body + artwork(r, 120, 150, 220, 150);
  body += rect(90, 430, 330, 160, '#B08660', 'rx="6"') + rect(90, 430, 330, 12, '#9C7552');
  body += `<ellipse cx="180" cy="410" rx="26" ry="24" fill="${pick(r, accents)}"/>` + rect(290, 380, 40, 50, '#F1EBE1', 'rx="6"');
  for (const cx of [420, 560, 700]) body += rect(cx - 40, 360, 80, 130, chair, 'rx="14"');
  body += rect(330, 480, 520, 22, '#4A3C30') + rect(370, 502, 14, 170, '#4A3C30') + rect(796, 502, 14, 170, '#4A3C30');
  for (const cx of [390, 560, 730]) body += rect(cx - 45, 520, 90, 18, chair, 'rx="6"') + rect(cx - 38, 538, 6, 120, chair) + rect(cx + 32, 538, 6, 120, chair);
  body += `<ellipse cx="560" cy="472" rx="40" ry="10" fill="#F6F1E8"/>` + pendant(560, 0, 260, BRONZE);
  return svg(floor.defs + view.defs, body);
}

function land(r: Rng) {
  const [top, bottom] = pick(r, skies);
  const hz = between(r, 360, 420);
  let body = rect(0, 0, W, H, 'url(#sky)');
  body += `<circle cx="${between(r, 200, 1000)}" cy="${between(r, 120, 200)}" r="60" fill="#FBF1E1" opacity=".9"/>`;
  body += `<path d="M0 ${hz} L0 ${hz - 120} L180 ${hz - 200} L340 ${hz - 110} L520 ${hz - 230} L700 ${hz - 120} L880 ${hz - 190} L1060 ${hz - 100} L1200 ${hz - 150} L1200 ${hz} Z" fill="#B8AC9A" opacity=".55"/>`;
  body += `<path d="M0 ${hz} L0 ${hz - 60} C240 ${hz - 110} 420 ${hz - 40} 640 ${hz - 80} S1000 ${hz - 40} 1200 ${hz - 70} L1200 ${hz} Z" fill="#A9A07F" opacity=".7"/>`;
  body += rect(0, hz, W, H - hz, 'url(#field)');
  for (let i = -8; i <= 8; i++) body += `<line x1="${600 + i * 30}" y1="${hz}" x2="${600 + i * 170}" y2="${H}" stroke="#7E6F4E" stroke-opacity=".18" stroke-width="2"/>`;
  for (let i = 0; i < 7; i++) body += cypress(80 + i * 40 + between(r, -6, 6), hz + 6, 90, '#6F7A5C');
  body += olive(1030, hz + 40, 0.8);
  for (let i = 0; i < 12; i++) {
    const x = 60 + i * 100;
    body += rect(x, hz + 170, 8, 60, '#7A6650');
  }
  body += rect(0, hz + 188, W, 4, '#7A6650', 'opacity=".7"');
  for (const [x, y] of [[430, hz + 120], [820, hz + 140], [600, hz + 300]]) {
    body += rect(x, y - 70, 6, 70, '#6B5A48') + `<path d="M${x + 6} ${y - 70} l46 14 l-46 14 Z" fill="${BRONZE}"/>`;
  }
  body += `<path d="M560 ${H} L640 ${hz + 40} L670 ${hz + 40} L760 ${H} Z" fill="#D9CDB4" opacity=".8"/>`;
  return svg(lg('sky', top, bottom) + lg('field', '#CDBB91', '#B39C6D'), body);
}

function shop(r: Rng) {
  const sky = skyAndHills(r, 300);
  const facade = pick(r, ['#EEE6DA', '#E5DACA', '#F2ECE2']);
  let body = sky.body + rect(60, 60, 1080, 600, facade);
  for (let c = 0; c < 4; c++) body += rect(120 + c * 260, 100, 160, 140, 'url(#glass)') + rect(110 + c * 260, 240, 180, 10, '#DCCFBC');
  body += rect(60, 290, 1080, 60, CHARCOAL) + rect(60, 344, 1080, 4, BRONZE);
  body += rect(470, 308, 260, 22, BRONZE, 'opacity=".9"');
  let awning = '';
  for (let i = 0; i < 18; i++) awning += `<path d="M${90 + i * 57} 350 h57 l-6 50 h-45 Z" fill="${i % 2 ? '#F4EEE4' : BRONZE}"/>`;
  body += awning;
  body += rect(90, 400, 1020, 260, CHARCOAL) + rect(110, 420, 780, 220, 'url(#shopLight)');
  for (let s = 0; s < 3; s++) body += rect(140, 460 + s * 60, 720, 8, '#B08660');
  for (let i = 0; i < 18; i++) body += rect(150 + i * 40, 430 + (i % 3) * 60, 24, 26, pick(r, accents), 'opacity=".75"');
  body += rect(910, 420, 180, 240, 'url(#glass)') + rect(1060, 520, 8, 40, BRONZE);
  body += rect(0, 660, W, 140, '#CFC4B2') + rect(0, 660, W, 10, '#BDB09B');
  body += plant(80, 690, 0.9) + plant(1130, 690, 0.9);
  return svg(sky.defs + lg('glass', '#56606A', GLASS) + lg('shopLight', '#F6E3C4', '#E4CBA4'), body);
}

function office(r: Rng) {
  const [top, bottom] = pick(r, skies);
  let body = rect(0, 0, W, H, '#EDE7DD') + rect(60, 60, 1080, 420, 'url(#sky)');
  for (let i = 0; i < 10; i++) body += rect(80 + i * 110, 480 - between(r, 100, 260), 80, 300, '#B9AE9B', 'opacity=".55"');
  for (let i = 0; i <= 6; i++) body += rect(60 + i * 180, 60, 8, 420, CHARCOAL);
  body += rect(60, 60, 1080, 8, CHARCOAL) + rect(60, 474, 1080, 8, CHARCOAL);
  body += rect(0, 560, W, 240, '#D3C9BA');
  for (const dx of [160, 640]) {
    body += rect(dx, 520, 400, 18, '#F6F2EA') + rect(dx + 10, 538, 8, 140, CHARCOAL) + rect(dx + 382, 538, 8, 140, CHARCOAL);
    body += rect(dx + 60, 440, 120, 80, '#2C2A28', 'rx="4"') + rect(dx + 112, 518, 16, 4, '#2C2A28');
    body += rect(dx + 240, 560, 110, 90, pick(r, accents), 'rx="20"') + rect(dx + 290, 650, 8, 60, CHARCOAL);
  }
  body += pendant(360, 0, 330) + pendant(840, 0, 330) + plant(1110, 700, 1.1);
  return svg(lg('sky', top, bottom), body);
}

const builders: Record<Scene, (r: Rng) => string> = {
  villa: (r) => villa(r, false),
  'villa-pool': (r) => villa(r, true),
  building,
  living,
  kitchen,
  bedroom,
  bathroom,
  dining,
  land,
  shop,
  office,
};

const cache = new Map<string, string>();

/** Returns a data URI for the scene; `seed` varies colours and layout. */
export function illustration(scene: Scene, seed = '') {
  const key = `${scene}|${seed}`;
  let uri = cache.get(key);
  if (!uri) {
    const markup = builders[scene](mulberry32(hash(key)));
    uri = `data:image/svg+xml;charset=utf-8,${encodeURIComponent(markup)}`;
    cache.set(key, uri);
  }
  return uri;
}
