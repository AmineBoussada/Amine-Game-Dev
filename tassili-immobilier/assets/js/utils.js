/* Shared helpers: DOM, storage, formatting, lookups, favourites, toast, WhatsApp links. */
window.TI = window.TI || {};
(function (TI) {
  TI.pages = TI.pages || {};
  TI.$ = function (s, r) { return (r || document).querySelector(s); };
  TI.$$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  TI.esc = function (s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  };
  TI.store = {
    get: function (k, d) { try { var v = localStorage.getItem(k); return v == null ? d : JSON.parse(v); } catch (e) { return d; } },
    set: function (k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} }
  };
  TI.debounce = function (fn, ms) { var t; return function () { var a = arguments, s = this; clearTimeout(t); t = setTimeout(function () { fn.apply(s, a); }, ms); }; };
  TI.params = function () { return new URLSearchParams(location.search); };
  TI.isRTL = function () { return document.documentElement.dir === 'rtl'; };

  /* Numbers: French groups with a narrow no-break space, Arabic (Algeria) with dots. Western digits in both. */
  TI.num = function (n) {
    var sep = TI.lang() === 'ar' ? '.' : ' ';
    return String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, sep);
  };
  TI.dec = function (x) {
    var r = Math.round(x * 10) / 10;
    return (r % 1 === 0 ? String(r) : r.toFixed(1)).replace('.', ',');
  };
  /* Price in dinars plus the spoken form: 1 milliard de centimes = 10 000 000 DA. */
  TI.price = function (p) {
    var main = '<span class="ltr">' + TI.num(p.price) + '</span> ' + TI.t('cur');
    var sub;
    if (p.tx === 'location') sub = TI.t('per_month');
    else {
      var m = p.price / 1e7;
      sub = m >= 1 ? TI.t(m < 2 ? 'milliard' : 'milliards', { n: TI.dec(m) }) : TI.t('millions', { n: TI.num(p.price / 1e4) });
    }
    return { main: main, sub: sub };
  };
  TI.digits = function (s) { var d = String(s || '').replace(/\D/g, ''); return d ? parseInt(d, 10) : 0; };

  TI.getWilaya = function (code) { code = Number(code); return TI.wilayas.filter(function (w) { return w.code === code; })[0]; };
  TI.getCommune = function (code, id) { var w = TI.getWilaya(code); return w && w.communes.filter(function (c) { return c.id === id; })[0]; };
  TI.getType = function (id) { return TI.types.filter(function (t) { return t.id === id; })[0]; };
  TI.getAgent = function (id) { return TI.agents.filter(function (a) { return a.id === id; })[0] || TI.agents[0]; };
  TI.findProperty = function (ref) { return TI.properties.filter(function (p) { return p.ref === ref; })[0]; };
  TI.place = function (p) {
    var w = TI.getWilaya(p.wilaya), c = TI.getCommune(p.wilaya, p.commune);
    return (c ? TI.L(c) : '') + (TI.lang() === 'ar' ? '، ' : ', ') + (w ? TI.L(w) : '');
  };
  TI.url = function (path, params) {
    var q = new URLSearchParams(params || {}).toString();
    return path + (q ? '?' + q : '');
  };

  TI.favs = {
    list: function () { var v = TI.store.get('ti-favs', []); return Array.isArray(v) ? v : []; },
    has: function (ref) { return TI.favs.list().indexOf(ref) > -1; },
    toggle: function (ref) {
      var l = TI.favs.list(), i = l.indexOf(ref), on = i < 0;
      if (on) l.push(ref); else l.splice(i, 1);
      TI.store.set('ti-favs', l);
      document.dispatchEvent(new CustomEvent('ti:favs', { detail: { ref: ref, on: on } }));
      return on;
    }
  };

  TI.toast = function (msg) {
    var region = TI.$('#toast-region');
    if (!region) return;
    region.innerHTML = '';
    var el = document.createElement('div');
    el.className = 'toast'; el.textContent = msg;
    region.appendChild(el);
    clearTimeout(TI._toastT);
    TI._toastT = setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, 2600);
  };

  TI.wa = function (text, number) {
    return 'https://wa.me/' + (number || TI.config.whatsapp) + '?text=' + encodeURIComponent(text);
  };
  TI.waProperty = function (p) {
    return TI.t('wa_prop', { ref: p.ref, title: TI.L(p.title), place: TI.place(p) });
  };
  TI.initials = function (name) { return name.split(/\s+/).map(function (w) { return w.charAt(0); }).slice(0, 2).join('').toUpperCase(); };
})(window.TI);
