/* Reusable UI: icons, header (with mobile drawer), footer, property card, WhatsApp button, theme and language switches. */
window.TI = window.TI || {};
(function (TI) {
  var P = {
    search: '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
    heart: '<path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10Z"/>',
    pin: '<path d="M12 21s7-6.2 7-12a7 7 0 0 0-14 0c0 5.8 7 12 7 12Z"/><circle cx="12" cy="9" r="2.5"/>',
    phone: '<path d="M5 4h3l2 5-2.5 1.5a11 11 0 0 0 6 6L15 14l5 2v3a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z"/>',
    mail: '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
    menu: '<path d="M4 7h16M4 12h16M4 17h16"/>',
    close: '<path d="M6 6l12 12M18 6 6 18"/>',
    chev: '<path d="m9 6 6 6-6 6"/>',
    arrow: '<path d="M5 12h14M13 6l6 6-6 6"/>',
    area: '<rect x="4" y="4" width="16" height="16" rx="1"/><path d="M4 9h5V4M20 15h-5v5"/>',
    bed: '<path d="M3 18v-7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v7M3 14h18M7 9V7a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v2"/>',
    bath: '<path d="M4 12h16v3a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4v-3ZM6 12V6a2 2 0 0 1 3.5-1.3M7 19l-1 2M17 19l1 2"/>',
    rooms: '<path d="M3 21V9l9-6 9 6v12"/><path d="M9 21v-7h6v7"/>',
    layers: '<path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/>',
    calendar: '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
    check: '<path d="m5 12 5 5 9-10"/>',
    minus: '<path d="M6 12h12"/>',
    share: '<circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="6" r="2.5"/><circle cx="18" cy="18" r="2.5"/><path d="m8.2 10.8 7.6-3.6M8.2 13.2l7.6 3.6"/>',
    grid: '<rect x="4" y="4" width="7" height="7" rx="1"/><rect x="13" y="4" width="7" height="7" rx="1"/><rect x="4" y="13" width="7" height="7" rx="1"/><rect x="13" y="13" width="7" height="7" rx="1"/>',
    list: '<rect x="4" y="5" width="6" height="5" rx="1"/><rect x="4" y="14" width="6" height="5" rx="1"/><path d="M13 7h7M13 16h7"/>',
    filter: '<path d="M4 6h16M7 12h10M10 18h4"/>',
    sun: '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
    moon: '<path d="M20 14.5A8 8 0 1 1 9.5 4a6.5 6.5 0 0 0 10.5 10.5Z"/>',
    globe: '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
    clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    camera: '<path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.5"/>',
    building: '<path d="M5 21V4h10v17M15 9h4v12M3 21h18M8 8h4M8 12h4M8 16h4"/>',
    villa: '<path d="M3 11 12 4l9 7M5 10v10h14V10M10 20v-6h4v6"/>',
    land: '<path d="M3 18c3-4 6-6 9-6s6 2 9 6"/><path d="M7 11V6M7 6 5 8M7 6l2 2M17 9V5"/><path d="M3 21h18"/>',
    shop: '<path d="M4 9h16l-1-5H5L4 9Zm0 0v11h16V9M10 20v-5h4v5"/>',
    duplex: '<path d="M4 21V8l5-4 5 4M14 21V11l3-2 3 2v10M2 21h20M7 13h4M7 17h4"/>',
    shield: '<path d="M12 3 5 6v6c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6l-7-3Z"/><path d="m9 12 2 2 4-4"/>',
    key: '<circle cx="8" cy="15" r="4"/><path d="m11 12 9-9M17 6l3 3M15 8l2 2"/>',
    doc: '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4M9 12h6M9 16h6"/>',
    bolt: '<path d="M13 3 5 14h6l-1 7 8-11h-6l1-7Z"/>',
    quote: '<path d="M7 17c-2 0-3-1.5-3-4 0-3 2-6 5-7M17 17c-2 0-3-1.5-3-4 0-3 2-6 5-7"/>',
    star: '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9L12 3Z"/>',
    external: '<path d="M14 4h6v6M20 4l-9 9M18 14v6H4V6h6"/>',
    users: '<circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-6 7-6s7 2.5 7 6M16 4a3.5 3.5 0 0 1 0 7M22 20c0-3-2-5-5-5.7"/>',
    instagram: '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1"/>',
    facebook: '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8Z"/>'
  };
  var WA = '<path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.3-.4.7-1.3a.5.5 0 0 0 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6a2.7 2.7 0 0 0 1.8-1.2 2.2 2.2 0 0 0 .1-1.3c0-.1-.2-.2-.4-.3Z"/>';
  TI.icon = function (name, cls) {
    cls = 'ic' + (cls ? ' ' + cls : '');
    if (name === 'wa') return '<svg class="' + cls + '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">' + WA + '</svg>';
    return '<svg class="' + cls + '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' + (P[name] || '') + '</svg>';
  };
  var LOGO = '<svg viewBox="0 0 34 34" aria-hidden="true" focusable="false"><path d="M3 29 L12 12 L17 20 L23 8 L31 29 Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><circle cx="26" cy="6" r="2.4" fill="#FFB703"/><path d="M3 29h28" stroke="#219EBC" stroke-width="2.4" stroke-linecap="round"/></svg>';
  function logo(cls) {
    return '<a class="logo ' + (cls || '') + '" href="index.html">' + LOGO + '<span class="logo-text"><span class="logo-a">' + TI.t('logo_a') + '</span><span class="logo-b">' + TI.t('logo_b') + '</span></span></a>';
  }

  /* Which nav item is current: derived from the page and its query string. */
  function currentNav() {
    var page = document.body.getAttribute('data-page'), p = TI.params();
    if (page === 'home') return 'home';
    if (page === 'about') return 'about';
    if (page === 'contact') return 'contact';
    if (page === 'archive') {
      if (p.get('neuf') === '1') return 'neuf';
      if (p.get('tx') === 'vente') return 'vente';
      if (p.get('tx') === 'location') return 'location';
    }
    return '';
  }
  function navItems() {
    return [
      ['home', 'index.html', 'nav_home'], ['vente', 'biens.html?tx=vente', 'nav_buy'], ['location', 'biens.html?tx=location', 'nav_rent'],
      ['neuf', 'biens.html?neuf=1', 'nav_new'], ['about', 'a-propos.html', 'nav_about'], ['contact', 'contact.html', 'nav_contact']
    ];
  }
  function isDark() {
    var t = document.documentElement.getAttribute('data-theme');
    if (t) return t === 'dark';
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  }

  TI.renderHeader = function () {
    var host = TI.$('#site-header');
    if (!host) return;
    var cur = currentNav(), n = TI.favs.list().length, c = TI.config;
    var links = navItems().map(function (it) {
      return '<li><a href="' + it[1] + '"' + (cur === it[0] ? ' aria-current="page"' : '') + '>' + TI.t(it[2]) + '</a></li>';
    }).join('');
    var dLinks = navItems().map(function (it) {
      return '<li><a href="' + it[1] + '"' + (cur === it[0] ? ' aria-current="page"' : '') + '>' + TI.t(it[2]) + TI.icon('chev', 'flip ic-sm') + '</a></li>';
    }).join('');
    host.className = 'site-header';
    host.innerHTML =
      '<div class="utility"><div class="container utility-in">' +
        '<span class="u-item">' + TI.icon('phone', 'ic-sm') + '<a class="ltr" href="tel:' + c.phoneHref + '">' + c.phone + '</a></span>' +
        '<span class="u-item u-hours">' + TI.icon('clock', 'ic-sm') + TI.esc(TI.L(c.hours)) + '</span>' +
        '<span class="u-spacer"></span>' +
        '<button type="button" class="lang-btn" data-lang-toggle lang="' + (TI.lang() === 'ar' ? 'fr' : 'ar') + '" aria-label="' + TI.t('lang_aria') + '">' + TI.icon('globe') + '<span>' + TI.t('lang_other') + '</span></button>' +
      '</div></div>' +
      '<div class="mainbar"><div class="container mainbar-in">' + logo() +
        '<nav class="nav" aria-label="' + TI.t('nav_label') + '"><ul>' + links + '</ul></nav>' +
        '<div class="actions">' +
          '<a class="icon-btn" href="biens.html?favoris=1" data-fav-link aria-label="' + TI.t(n ? 'favorites_n' : 'favorites', { n: n }) + '">' + TI.icon('heart') + '<span class="fav-count" data-fav-count' + (n ? '' : ' hidden') + '>' + n + '</span></a>' +
          '<button type="button" class="icon-btn" data-theme-toggle aria-label="' + TI.t(isDark() ? 'theme_light' : 'theme_dark') + '">' + TI.icon(isDark() ? 'sun' : 'moon') + '</button>' +
          '<a class="btn btn-gold btn-sm hide-md" href="contact.html?sujet=vendre">' + TI.t('cta_list') + '</a>' +
          '<a class="btn btn-wa btn-sm btn-icon hide-md" href="' + TI.wa(TI.t('wa_general')) + '" target="_blank" rel="noopener" aria-label="' + TI.t('wa_write') + '">' + TI.icon('wa') + '</a>' +
          '<button type="button" class="icon-btn burger" data-drawer-open aria-expanded="false" aria-controls="drawer" aria-label="' + TI.t('menu_open') + '">' + TI.icon('menu') + '</button>' +
        '</div>' +
      '</div></div>' +
      '<div class="drawer" id="drawer" hidden>' +
        '<div class="drawer-backdrop" data-drawer-close></div>' +
        '<div class="drawer-panel" role="dialog" aria-modal="true" aria-label="' + TI.t('menu') + '">' +
          '<div class="drawer-head">' + logo() + '<button type="button" class="icon-btn" data-drawer-close aria-label="' + TI.t('menu_close') + '">' + TI.icon('close') + '</button></div>' +
          '<ul class="drawer-nav">' + dLinks + '</ul>' +
          '<div class="drawer-foot">' +
            '<a class="btn btn-gold" href="contact.html?sujet=vendre">' + TI.t('cta_list') + '</a>' +
            '<a class="btn btn-wa" href="' + TI.wa(TI.t('wa_general')) + '" target="_blank" rel="noopener">' + TI.icon('wa') + TI.t('wa_write') + '</a>' +
            '<a class="btn btn-ghost" href="tel:' + c.phoneHref + '">' + TI.icon('phone') + TI.t('call') + ' <span class="ltr">' + c.phone + '</span></a>' +
          '</div>' +
        '</div>' +
      '</div>';
  };

  TI.renderFooter = function () {
    var host = TI.$('#site-footer');
    if (!host) return;
    var c = TI.config;
    var wil = TI.wilayas.slice(0, 6).map(function (w) { return '<li><a href="biens.html?wilaya=' + w.code + '">' + TI.esc(TI.L(w)) + '</a></li>'; }).join('');
    function acc(title, body) { return '<details class="f-acc" open><summary><h3>' + title + '</h3></summary>' + body + '</details>'; }
    host.className = 'site-footer';
    host.innerHTML =
      '<div class="container"><div class="footer-grid">' +
        '<div class="footer-brand">' + logo() + '<p>' + TI.t('footer_about') + '</p>' +
          '<div class="social"><a href="' + c.facebook + '" target="_blank" rel="noopener" aria-label="' + TI.t('social_fb') + '">' + TI.icon('facebook') + '</a><a href="' + c.instagram + '" target="_blank" rel="noopener" aria-label="' + TI.t('social_ig') + '">' + TI.icon('instagram') + '</a><a href="' + TI.wa(TI.t('wa_general')) + '" target="_blank" rel="noopener" aria-label="' + TI.t('wa_write') + '">' + TI.icon('wa') + '</a></div>' +
        '</div>' +
        acc(TI.t('footer_links'), '<ul><li><a href="biens.html?tx=vente">' + TI.t('nav_buy') + '</a></li><li><a href="biens.html?tx=location">' + TI.t('nav_rent') + '</a></li><li><a href="biens.html?neuf=1">' + TI.t('nav_new') + '</a></li><li><a href="contact.html?sujet=vendre">' + TI.t('cta_list') + '</a></li><li><a href="a-propos.html">' + TI.t('nav_about') + '</a></li><li><a href="contact.html">' + TI.t('nav_contact') + '</a></li></ul>') +
        acc(TI.t('footer_wilayas'), '<ul>' + wil + '</ul>') +
        acc(TI.t('footer_contact'), '<ul class="contact-list">' +
          '<li>' + TI.icon('pin', 'ic-sm') + '<span>' + TI.esc(TI.L(c.address)) + '</span></li>' +
          '<li>' + TI.icon('phone', 'ic-sm') + '<a class="ltr" href="tel:' + c.phoneHref + '">' + c.phone + '</a></li>' +
          '<li>' + TI.icon('wa', 'ic-sm') + '<a href="' + TI.wa(TI.t('wa_general')) + '" target="_blank" rel="noopener">' + TI.t('wa_write') + '</a></li>' +
          '<li>' + TI.icon('mail', 'ic-sm') + '<a href="mailto:' + c.email + '">' + c.email + '</a></li>' +
          '<li>' + TI.icon('clock', 'ic-sm') + '<span>' + TI.esc(TI.L(c.hours)) + '<br>' + TI.esc(TI.L(c.saturday)) + '</span></li></ul>') +
      '</div>' +
      '<div class="footer-bottom"><span>' + TI.t('footer_rights') + '</span><nav aria-label="' + TI.t('footer_legal') + '"><a href="a-propos.html#mentions">' + TI.t('footer_legal') + '</a><a href="a-propos.html#confidentialite">' + TI.t('footer_privacy') + '</a></nav></div>' +
      '</div>';
  };

  TI.renderFab = function () {
    var el = TI.$('#wa-fab');
    if (!el) { el = document.createElement('a'); el.id = 'wa-fab'; el.className = 'wa-fab'; el.target = '_blank'; el.rel = 'noopener'; document.body.appendChild(el); }
    el.href = TI.wa(TI.t('wa_general'));
    el.setAttribute('aria-label', TI.t('wa_fab'));
    el.innerHTML = TI.icon('wa');
    if (!TI.$('#toast-region')) { var r = document.createElement('div'); r.id = 'toast-region'; r.className = 'toast-region'; r.setAttribute('role', 'status'); r.setAttribute('aria-live', 'polite'); document.body.appendChild(r); }
  };

  TI.renderChrome = function () { TI.renderHeader(); TI.renderFooter(); TI.renderFab(); };

  /* ---- Property card ---- */
  TI.badges = function (p) {
    var b = '<span class="badge ' + (p.tx === 'vente' ? 'badge-sale' : 'badge-rent') + '">' + TI.t('tx_' + p.tx) + '</span>';
    if (p.isNew) b += '<span class="badge badge-new">' + TI.t('badge_new') + '</span>';
    if (p.exclusive) b += '<span class="badge">' + TI.t('badge_excl') + '</span>';
    if ((p.amen || []).indexOf('meuble') > -1) b += '<span class="badge">' + TI.esc(TI.L(TI.amenities.filter(function (a) { return a.id === 'meuble'; })[0])) + '</span>';
    return b;
  };
  TI.card = function (p) {
    var pr = TI.price(p), href = 'bien.html?ref=' + encodeURIComponent(p.ref), fav = TI.favs.has(p.ref), shots = TI.media.gallery(p);
    var facts = '<li>' + TI.icon('area') + '<b class="ltr">' + TI.num(p.surface) + ' m²</b></li>';
    if (p.rooms) facts += '<li>' + TI.icon('rooms') + TI.t('rooms_n', { n: '<b>' + p.rooms + '</b>' }) + '</li>';
    if (p.beds) facts += '<li>' + TI.icon('bed') + TI.t('beds_n', { n: '<b>' + p.beds + '</b>' }) + '</li>';
    if (p.isNew && p.delivery) facts += '<li>' + TI.icon('calendar') + TI.esc(TI.L(p.delivery)) + '</li>';
    else if (p.papers.indexOf('acte') > -1) facts += '<li class="ok">' + TI.icon('check') + TI.t('papers_ok') + '</li>';
    return '<article class="card" data-ref="' + p.ref + '">' +
      '<div class="card-media"><img src="' + shots[0].src + '" alt="' + TI.esc(TI.L(p.title)) + '" width="800" height="600" loading="lazy" decoding="async">' +
        '<div class="card-badges">' + TI.badges(p) + '</div>' +
        '<button type="button" class="fav" data-fav="' + p.ref + '" aria-pressed="' + fav + '" aria-label="' + TI.t(fav ? 'fav_remove' : 'fav_add') + '">' + TI.icon('heart') + '</button>' +
        '<span class="card-ref ltr">' + p.ref + '</span><span class="card-count">' + TI.icon('camera') + TI.t('photos_n', { n: shots.length }) + '</span>' +
      '</div>' +
      '<div class="card-body">' +
        '<p class="price">' + pr.main + '<small>' + pr.sub + '</small></p>' +
        '<h3 class="card-title"><a class="card-link" href="' + href + '">' + TI.esc(TI.L(p.title)) + '</a></h3>' +
        '<p class="card-loc">' + TI.icon('pin') + TI.esc(TI.place(p)) + '</p>' +
        '<ul class="facts">' + facts + '</ul>' +
      '</div>' +
      '<div class="card-foot"><a class="btn btn-ghost btn-sm" href="' + href + '" tabindex="-1" aria-hidden="true">' + TI.t('see_property') + '</a>' +
        '<a class="btn btn-wa btn-sm btn-icon" href="' + TI.wa(TI.waProperty(p), TI.getAgent(p.agent).whatsapp) + '" target="_blank" rel="noopener" aria-label="' + TI.t('wa_card', { ref: p.ref }) + '">' + TI.icon('wa') + '</a></div>' +
    '</article>';
  };

  /* ---- Global behaviour (bound once, delegated) ---- */
  var lastFocus = null;
  function openDrawer() {
    var d = TI.$('#drawer'); if (!d) return;
    lastFocus = document.activeElement;
    d.hidden = false; document.body.classList.add('no-scroll');
    TI.$$('[data-drawer-open]').forEach(function (b) { b.setAttribute('aria-expanded', 'true'); });
    var first = TI.$('.drawer-panel a, .drawer-panel button', d); if (first) first.focus();
  }
  function closeDrawer() {
    var d = TI.$('#drawer'); if (!d || d.hidden) return;
    d.hidden = true; document.body.classList.remove('no-scroll');
    TI.$$('[data-drawer-open]').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
    if (lastFocus && lastFocus.focus) lastFocus.focus();
  }
  TI.closeDrawer = closeDrawer;

  TI.bindGlobal = function () {
    document.addEventListener('click', function (e) {
      var t = e.target.closest ? e.target : e.target.parentElement;
      if (!t) return;
      if (t.closest('[data-drawer-open]')) { openDrawer(); return; }
      if (t.closest('[data-drawer-close]')) { closeDrawer(); return; }
      if (t.closest('.drawer-nav a')) closeDrawer();
      if (t.closest('[data-lang-toggle]')) { closeDrawer(); TI.setLang(TI.lang() === 'ar' ? 'fr' : 'ar'); return; }
      if (t.closest('[data-theme-toggle]')) {
        var next = isDark() ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        try { localStorage.setItem('ti-theme', next); } catch (err) {}
        TI.renderHeader();
        var tb = TI.$('[data-theme-toggle]'); if (tb) tb.focus();
        return;
      }
      var fav = t.closest('[data-fav]');
      if (fav) {
        e.preventDefault();
        var on = TI.favs.toggle(fav.getAttribute('data-fav'));
        TI.$$('[data-fav="' + fav.getAttribute('data-fav') + '"]').forEach(function (b) {
          b.setAttribute('aria-pressed', String(on));
          b.setAttribute('aria-label', TI.t(on ? 'fav_remove' : 'fav_add'));
        });
        TI.toast(TI.t(on ? 'fav_added' : 'fav_removed'));
      }
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeDrawer();
      if (e.key === 'Tab') {
        var d = TI.$('#drawer');
        if (d && !d.hidden) {
          var f = TI.$$('a, button', TI.$('.drawer-panel', d)); if (!f.length) return;
          if (e.shiftKey && document.activeElement === f[0]) { e.preventDefault(); f[f.length - 1].focus(); }
          else if (!e.shiftKey && document.activeElement === f[f.length - 1]) { e.preventDefault(); f[0].focus(); }
        }
      }
    });
    document.addEventListener('ti:favs', function () {
      var n = TI.favs.list().length;
      TI.$$('[data-fav-count]').forEach(function (el) { el.textContent = n; el.hidden = !n; });
      TI.$$('[data-fav-link]').forEach(function (el) { el.setAttribute('aria-label', TI.t(n ? 'favorites_n' : 'favorites', { n: n })); });
    });
    var onScroll = function () { var h = TI.$('#site-header'); if (h) h.classList.toggle('scrolled', window.scrollY > 40); };
    window.addEventListener('scroll', onScroll, { passive: true }); onScroll();
    if (window.matchMedia) {
      var mq = window.matchMedia('(prefers-color-scheme: dark)');
      var re = function () { if (!document.documentElement.getAttribute('data-theme')) TI.renderHeader(); };
      if (mq.addEventListener) mq.addEventListener('change', re);
    }
    window.addEventListener('resize', function () { if (window.innerWidth >= 1080) closeDrawer(); });
  };
})(window.TI);
