/* Placeholder photography: seeded SVG scenes (façades, interiors, land, shops) in the brand palette.
   Each property can instead list real photos in p.photos = [{src, shot}] and those are used as-is. */
window.TI = window.TI || {};
(function (TI) {
  function rng(seed) { var s = (seed * 2654435761) % 4294967296 || 1; return function () { s = (s * 1664525 + 1013904223) % 4294967296; return s / 4294967296; }; }
  function pick(r, a) { return a[Math.floor(r() * a.length)]; }
  function rect(x, y, w, h, f, extra) { return '<rect x="' + x + '" y="' + y + '" width="' + w + '" height="' + h + '" fill="' + f + '"' + (extra || '') + '/>'; }
  function svg(inner, defs) {
    return 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 600" preserveAspectRatio="xMidYMid slice"><defs>' + (defs || '') + '</defs>' + inner + '</svg>');
  }
  var SKY = { day: ['#7FC3E3', '#D9F0FA'], dusk: ['#219EBC', '#FFD27A'], sunset: ['#023047', '#FB8500'] };
  function sky(t) {
    return '<linearGradient id="sky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="' + SKY[t][0] + '"/><stop offset="1" stop-color="' + SKY[t][1] + '"/></linearGradient>' +
      '<linearGradient id="sea" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="' + (t === 'sunset' ? '#0B4A66' : '#219EBC') + '"/><stop offset="1" stop-color="' + (t === 'sunset' ? '#023047' : '#157A93') + '"/></linearGradient>';
  }
  function backdrop(r, t, o) {
    var s = rect(0, 0, 800, 600, 'url(#sky)');
    var sunY = t === 'day' ? 110 : t === 'dusk' ? 260 : 330;
    s += '<circle cx="' + (560 + Math.floor(r() * 160)) + '" cy="' + sunY + '" r="44" fill="' + (t === 'day' ? '#FFF7DA' : '#FFB703') + '" opacity=".92"/>';
    if (t === 'day') s += '<ellipse cx="' + (120 + r() * 200) + '" cy="90" rx="70" ry="16" fill="#fff" opacity=".7"/><ellipse cx="' + (380 + r() * 120) + '" cy="140" rx="50" ry="12" fill="#fff" opacity=".55"/>';
    s += '<path d="M0 395 Q160 330 330 372 T640 352 T800 360 V600 H0Z" fill="' + (t === 'sunset' ? '#0B3D55' : '#6FAFC9') + '" opacity=".55"/>';
    if (o.sea) s += rect(0, 392, 800, 76, 'url(#sea)') + '<path d="M40 412h120M260 430h160M520 418h140M120 448h90" stroke="#fff" stroke-width="2" opacity=".35"/>';
    s += rect(0, 462, 800, 138, t === 'sunset' ? '#3A4A3A' : '#D7CBA9');
    return s;
  }
  function palm(x, y, k) {
    k = k || 1;
    return '<g transform="translate(' + x + ' ' + y + ') scale(' + k + ')"><path d="M0 0 C 6 -60 -4 -120 10 -180" stroke="#7A5A3A" stroke-width="9" fill="none" stroke-linecap="round"/>' +
      '<g fill="#2F6B4F"><path d="M10 -180 C -30 -200 -70 -190 -95 -160 C -60 -175 -30 -175 10 -180Z"/><path d="M10 -180 C 50 -205 90 -195 110 -165 C 75 -180 45 -178 10 -180Z"/>' +
      '<path d="M10 -180 C -10 -220 -45 -235 -75 -230 C -40 -220 -20 -205 10 -180Z"/><path d="M10 -180 C 30 -225 65 -238 95 -228 C 60 -218 35 -205 10 -180Z"/><path d="M10 -180 C 5 -215 15 -245 30 -255 C 22 -228 18 -205 10 -180Z"/></g></g>';
  }
  function windows(x, y, cols, rows, w, h, gx, gy, glass, shutter) {
    var s = '';
    for (var i = 0; i < cols; i++) for (var j = 0; j < rows; j++) {
      var wx = x + i * (w + gx), wy = y + j * (h + gy);
      s += rect(wx, wy, w, h, glass);
      if (shutter) s += rect(wx - 9, wy, 8, h, shutter) + rect(wx + w + 1, wy, 8, h, shutter);
    }
    return s;
  }
  function tint(t) { return t === 'sunset' ? rect(0, 0, 800, 600, '#FB8500', ' opacity=".12"') : t === 'dusk' ? rect(0, 0, 800, 600, '#FFB703', ' opacity=".07"') : ''; }

  function exterior(kind, seed, o) {
    o = o || {};
    var r = rng(seed), t = o.time || pick(r, ['day', 'day', 'dusk', 'sunset']);
    var wall = pick(r, ['#FFFFFF', '#F6F1E7', '#EEE6D6', '#F2F5F7']);
    var glass = t === 'sunset' ? '#FFC94D' : t === 'dusk' ? '#BFE3F2' : '#8ECAE6';
    var shutter = pick(r, ['#219EBC', '#157A93', '#2C7DA0']);
    var s = backdrop(r, t, o);
    if (kind === 'villa') {
      s += rect(150, 280, 460, 190, wall) + rect(140, 270, 480, 14, '#E1D6C2');
      s += rect(220, 180, 320, 100, wall) + rect(212, 172, 336, 12, '#E1D6C2');
      s += windows(195, 315, 4, 1, 54, 74, 46, 0, glass, shutter);
      s += windows(255, 205, 3, 1, 48, 56, 52, 0, glass, shutter);
      s += '<path d="M355 470 V405 A25 25 0 0 1 405 405 V470Z" fill="#8A6A48"/>';
      if (o.pool) s += '<path d="M120 520 L680 520 L720 570 L80 570Z" fill="#5CC2E0"/><path d="M160 540h140M380 552h180" stroke="#fff" stroke-width="3" opacity=".6"/>';
      s += rect(40, 438, 110, 34, '#3E7A5A', ' rx="14"') + rect(612, 438, 140, 34, '#3E7A5A', ' rx="14"');
      s += palm(95, 470, 1) + palm(700, 474, .8);
    } else if (kind === 'appartement' || kind === 'residence') {
      var blocks = [[90, 210, 200, 260], [300, 150, 220, 320], [530, 240, 190, 230]];
      blocks.forEach(function (b, i) {
        var c = i === 1 ? wall : pick(r, ['#F6F1E7', '#E9EFF2', '#FFFFFF']);
        s += rect(b[0], b[1], b[2], b[3], c);
        var rows = Math.floor((b[3] - 40) / 44), cols = Math.floor((b[2] - 20) / 46);
        s += windows(b[0] + 16, b[1] + 20, cols, rows, 30, 26, 16, 18, glass);
        for (var j = 0; j < rows; j++) s += rect(b[0] + 8, b[1] + 48 + j * 44, b[2] - 16, 4, '#C7D3DA');
      });
      s += palm(70, 472, .7) + palm(740, 472, .65);
    } else if (kind === 'duplex') {
      s += rect(170, 200, 460, 270, wall) + rect(240, 140, 320, 64, wall);
      s += windows(200, 230, 5, 3, 50, 44, 34, 30, glass, shutter);
      s += '<path d="M240 140h320" stroke="#3F5C6D" stroke-width="3"/><path d="M230 132 v-18 M270 132 v-18 M310 132 v-18 M350 132 v-18 M390 132 v-18 M430 132 v-18 M470 132 v-18 M510 132 v-18 M550 132 v-18 M570 132 v-18" stroke="#3F5C6D" stroke-width="2"/><path d="M228 114h344" stroke="#3F5C6D" stroke-width="3"/>';
      s += rect(150, 190, 500, 10, '#E1D6C2') + palm(110, 472, .9) + rect(560, 112, 50, 30, '#3E7A5A', ' rx="12"');
    } else if (kind === 'terrain') {
      s += '<path d="M0 470 Q200 430 420 455 T800 445 V600 H0Z" fill="' + (t === 'sunset' ? '#5A5A3A' : '#C8B98C') + '"/>';
      for (var k = 0; k < 6; k++) { var tx = 60 + k * 130 + r() * 40, ty = 450 + r() * 20; s += rect(tx - 4, ty - 30, 8, 34, '#6B4F35') + '<circle cx="' + tx + '" cy="' + (ty - 44) + '" r="' + (24 + r() * 10) + '" fill="#6B8F5E"/><circle cx="' + (tx + 16) + '" cy="' + (ty - 36) + '" r="18" fill="#7FA26E"/>'; }
      for (var f = 0; f < 16; f++) s += rect(20 + f * 50, 520, 5, 40, '#8A6A48');
      s += '<path d="M20 532 H780" stroke="#8A6A48" stroke-width="3"/>' + rect(560, 470, 120, 66, '#FB8500', ' rx="4"') + rect(616, 536, 8, 40, '#6B4F35');
    } else if (kind === 'local') {
      s += rect(130, 160, 540, 310, wall);
      s += windows(165, 190, 6, 2, 56, 50, 30, 26, glass, shutter);
      s += rect(150, 360, 500, 110, '#0E3A50') + rect(162, 372, 230, 94, glass) + rect(408, 372, 230, 94, glass);
      for (var a = 0; a < 10; a++) s += '<path d="M' + (140 + a * 52) + ' 340 h52 l-8 24 h-36Z" fill="' + (a % 2 ? '#FFFFFF' : '#FB8500') + '"/>';
      s += palm(80, 470, .75);
    }
    return svg(s + tint(t), sky(t));
  }

  function interior(room, seed) {
    var r = rng(seed);
    var wall = pick(r, ['#F4EFE6', '#E8F1F5', '#F5F1EA', '#ECE7DF']);
    var floor = pick(r, ['#C9B79C', '#B89C7D', '#D8D2C7', '#BFAE95']);
    var s = rect(0, 0, 800, 430, wall) + rect(0, 430, 800, 170, floor);
    for (var i = 1; i < 8; i++) s += '<path d="M' + (i * 100) + ' 430 L' + (i * 100 + (i - 4) * 60) + ' 600" stroke="#000" stroke-opacity=".06" stroke-width="2"/>';
    var winX = room === 'sdb' ? 560 : 470;
    s += rect(winX - 10, 70, 220, 270, '#FFFFFF') + '<path d="M' + winX + ' 330 V140 A100 70 0 0 1 ' + (winX + 200) + ' 140 V330Z" fill="url(#sky)"/>';
    s += '<path d="M' + (winX + 100) + ' 80 V330 M' + winX + ' 220 H' + (winX + 200) + '" stroke="#FFFFFF" stroke-width="8"/>';
    s += '<path d="M' + winX + ' 330 L' + (winX - 120) + ' 600 L' + (winX + 320) + ' 600 L' + (winX + 200) + ' 330Z" fill="#FFF7DA" opacity=".18"/>';
    if (room === 'salon') {
      s += '<ellipse cx="300" cy="520" rx="240" ry="50" fill="#219EBC" opacity=".35"/>';
      s += rect(80, 330, 340, 110, '#023047', ' rx="18"') + rect(60, 360, 40, 100, '#023047', ' rx="14"') + rect(400, 360, 40, 100, '#023047', ' rx="14"') + rect(90, 300, 320, 60, '#034463', ' rx="16"');
      s += rect(130, 312, 70, 50, '#FFB703', ' rx="10"') + rect(300, 312, 70, 50, '#8ECAE6', ' rx="10"');
      s += rect(220, 470, 160, 16, '#8A6A48', ' rx="6"') + rect(232, 486, 10, 40, '#8A6A48') + rect(358, 486, 10, 40, '#8A6A48');
      s += rect(120, 130, 220, 140, '#FFFFFF') + rect(132, 142, 196, 116, '#FB8500', ' opacity=".55"') + '<circle cx="230" cy="200" r="30" fill="#023047" opacity=".5"/>';
      s += rect(720, 380, 44, 60, '#C46A2F', ' rx="6"') + '<path d="M742 380 C 720 330 700 320 690 300 M742 380 C 760 320 780 310 790 290 M742 380 C 742 330 745 300 742 270" stroke="#2F6B4F" stroke-width="10" fill="none" stroke-linecap="round"/>';
    } else if (room === 'cuisine') {
      s += rect(40, 330, 400, 120, '#FFFFFF') + rect(30, 316, 420, 16, '#3F5C6D');
      for (var k = 0; k < 4; k++) s += rect(52 + k * 98, 346, 86, 90, '#F2F5F7') + rect(84 + k * 98, 356, 22, 4, '#9CB2BF');
      s += rect(40, 120, 400, 110, '#FFFFFF') + '<path d="M140 120v110M240 120v110M340 120v110" stroke="#D9E2E7" stroke-width="3"/>';
      s += rect(40, 232, 400, 84, '#219EBC', ' opacity=".25"');
      for (var z = 0; z < 10; z++) s += rect(44 + z * 40, 240, 32, 32, z % 2 ? '#219EBC' : '#8ECAE6', ' opacity=".5"');
      s += '<path d="M190 120 L210 60 H290 L310 120Z" fill="#C7D3DA"/>' + rect(700, 180, 80, 270, '#DDE5EA', ' rx="6"') + rect(712, 300, 6, 40, '#9CB2BF');
    } else if (room === 'chambre') {
      s += rect(90, 220, 380, 110, '#8A6A48', ' rx="10"') + rect(70, 320, 420, 150, '#E2F0F7', ' rx="16"') + rect(70, 380, 420, 90, '#219EBC', ' rx="14" opacity=".55"');
      s += rect(110, 290, 130, 50, '#FFFFFF', ' rx="14"') + rect(260, 290, 130, 50, '#FFFFFF', ' rx="14"') + rect(330, 300, 90, 40, '#FFB703', ' rx="12"');
      s += rect(10, 380, 56, 70, '#C9B79C') + rect(494, 380, 56, 70, '#C9B79C') + '<path d="M38 380 L26 340 H50Z" fill="#FFB703"/><path d="M522 380 L510 340 H534Z" fill="#FFB703"/>';
    } else if (room === 'sdb') {
      for (var x = 0; x < 13; x++) for (var y = 0; y < 6; y++) s += rect(x * 42, 180 + y * 42, 40, 40, (x + y) % 3 ? '#E2F0F7' : '#8ECAE6');
      s += rect(30, 400, 420, 90, '#FFFFFF', ' rx="40"') + rect(50, 410, 380, 30, '#BFE3F2', ' rx="14"');
      s += '<ellipse cx="660" cy="190" rx="70" ry="90" fill="#DDEFF7" stroke="#C9B79C" stroke-width="8"/>' + rect(590, 320, 140, 120, '#8A6A48') + rect(580, 306, 160, 18, '#FFFFFF');
    } else {
      s += rect(60, 200, 300, 230, '#FFFFFF') + windows(80, 220, 4, 3, 50, 50, 20, 20, '#E2F0F7');
      s += rect(420, 380, 260, 18, '#3F5C6D') + rect(440, 398, 12, 60, '#3F5C6D') + rect(648, 398, 12, 60, '#3F5C6D');
      s += '<circle cx="250" cy="80" r="24" fill="#FFF7DA"/><circle cx="550" cy="80" r="24" fill="#FFF7DA"/>';
    }
    return svg(s, sky('day'));
  }

  function outdoor(kind, seed, o) {
    var r = rng(seed), t = o.time || pick(r, ['day', 'dusk', 'sunset']);
    var s = backdrop(r, t, { sea: kind !== 'jardin' || o.sea });
    if (kind === 'jardin') {
      s += rect(0, 440, 800, 160, '#5E9E6E') + '<path d="M330 600 Q380 520 420 440 L460 440 Q450 520 470 600Z" fill="#D7CBA9"/>';
      for (var i = 0; i < 5; i++) s += '<circle cx="' + (60 + i * 170) + '" cy="' + (400 - r() * 30) + '" r="' + (50 + r() * 30) + '" fill="#3E7A5A"/>';
      s += palm(640, 470, .9);
    } else {
      s += rect(0, 470, 800, 130, '#E9E1CF') + '<path d="M0 470 H800" stroke="#FFFFFF" stroke-width="6"/>';
      for (var p = 0; p < 17; p++) s += rect(p * 50, 410, 6, 62, '#FFFFFF');
      s += rect(0, 404, 800, 8, '#FFFFFF');
      s += '<ellipse cx="420" cy="540" rx="70" ry="16" fill="#8A6A48"/>' + rect(414, 540, 12, 40, '#8A6A48') + rect(300, 500, 60, 70, '#023047', ' rx="8"') + rect(480, 500, 60, 70, '#023047', ' rx="8"');
      s += rect(700, 470, 50, 70, '#C46A2F', ' rx="6"') + '<circle cx="725" cy="450" r="34" fill="#3E7A5A"/>';
    }
    return svg(s + tint(t), sky(t));
  }

  var SHOTS = {
    villa: ['facade', 'salon', 'cuisine', 'chambre', 'sdb', 'jardin', 'terrasse'],
    appartement: ['residence', 'salon', 'cuisine', 'chambre', 'sdb', 'vue'],
    duplex: ['facade', 'salon', 'terrasse', 'cuisine', 'chambre', 'sdb'],
    terrain: ['terrain', 'acces', 'vue'],
    local: ['vitrine', 'interieur', 'acces']
  };
  var cache = {};
  TI.media = {
    exterior: exterior,
    interior: interior,
    gallery: function (p) {
      if (p.photos && p.photos.length) return p.photos;
      if (cache[p.ref]) return cache[p.ref];
      var sea = (p.amen || []).indexOf('vue_mer') > -1, pool = (p.amen || []).indexOf('piscine') > -1;
      var time = ['day', 'dusk', 'sunset', 'day'][p.seed % 4];
      cache[p.ref] = SHOTS[p.type].map(function (shot, i) {
        var seed = p.seed * 31 + i * 7, src;
        if (shot === 'facade' || shot === 'residence') src = exterior(p.type === 'appartement' ? 'residence' : p.type, p.seed, { sea: sea, pool: pool, time: time });
        else if (shot === 'terrain') src = exterior('terrain', p.seed, { sea: sea, time: time });
        else if (shot === 'vitrine') src = exterior('local', p.seed, { time: time });
        else if (shot === 'acces') src = exterior(p.type === 'terrain' ? 'terrain' : 'appartement', seed, { sea: false });
        else if (shot === 'jardin' || shot === 'terrasse' || shot === 'vue') src = outdoor(shot === 'jardin' ? 'jardin' : 'terrasse', seed, { sea: sea || shot === 'vue', time: shot === 'vue' ? 'dusk' : undefined });
        else src = interior(shot, seed);
        return { src: src, shot: shot };
      });
      return cache[p.ref];
    },
    cover: function (p) { return TI.media.gallery(p)[0].src; },
    hero: function () { return exterior('villa', 7, { time: 'sunset', sea: true, pool: true }); }
  };
})(window.TI);
