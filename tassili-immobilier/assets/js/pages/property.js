/* Property page: gallery + lightbox (keyboard, swipe, RTL-aware), facts, papers, amenities, agent panel,
   visit request dialog, share, similar listings, sticky mobile action bar. */
window.TI = window.TI || {};
TI.pages = TI.pages || {};
TI.pages.property = function () {
  var root = TI.$('#property');
  var ref = TI.params().get('ref');
  var p = TI.findProperty(ref);
  var idx = 0;
  var visitDone = null;

  if (!p) {
    var nf = function () {
      document.title = TI.t('p_notfound_title') + TI.t('meta_suffix');
      root.innerHTML = '<div class="container notfound">' + TI.icon('search', 'ic-lg') + '<h1>' + TI.t('p_notfound_title') + '</h1><p class="muted">' + TI.t('p_notfound_text') + '</p><a class="btn btn-primary" href="biens.html">' + TI.t('p_back') + '</a></div>';
    };
    nf();
    return { render: nf };
  }

  var shots = TI.media.gallery(p);
  var agent = TI.getAgent(p.agent);
  function cap(i) { return TI.t('shot_' + shots[i].shot); }
  function waHref() { return TI.wa(TI.waProperty(p), agent.whatsapp); }

  function facts() {
    var f = [];
    f.push(['area', 'fact_surface', '<span class="ltr">' + TI.num(p.surface) + ' m²</span>']);
    if (p.land) f.push(['land', 'fact_land', '<span class="ltr">' + TI.num(p.land) + ' m²</span>']);
    if (p.typo) f.push(['rooms', 'fact_typo', p.typo]);
    if (p.beds) f.push(['bed', 'fact_beds', p.beds]);
    if (p.baths) f.push(['bath', 'fact_baths', p.baths]);
    if (p.floor != null) f.push(['layers', 'fact_floor', p.floor === 0 ? TI.t('floor_rdc') : p.floor === 1 ? TI.t('floor_1') : TI.t('floor_n', { n: p.floor })]);
    if (p.floors) f.push(['layers', 'fact_floors', p.floors]);
    if (p.isNew && p.delivery) f.push(['calendar', 'fact_delivery', TI.esc(TI.L(p.delivery))]);
    else if (p.year) f.push(['calendar', 'fact_year', p.year]);
    return f.map(function (x) { return '<li>' + TI.icon(x[0]) + '<div><span>' + TI.t(x[1]) + '</span><b>' + x[2] + '</b></div></li>'; }).join('');
  }

  function similar() {
    var others = TI.properties.filter(function (q) { return q.ref !== p.ref; });
    var score = function (q) { return (q.type === p.type ? 2 : 0) + (q.wilaya === p.wilaya ? 2 : 0) + (q.tx === p.tx ? 1 : 0); };
    return others.sort(function (a, b) { return score(b) - score(a) || (a.date < b.date ? 1 : -1); }).slice(0, 3);
  }

  function render() {
    var pr = TI.price(p), w = TI.getWilaya(p.wilaya), c = TI.getCommune(p.wilaya, p.commune);
    document.title = TI.L(p.title) + ' · ' + TI.place(p) + TI.t('meta_suffix');
    var thumbs = shots.map(function (sh, i) {
      var extra = (i === 3 && shots.length > 4) ? '<span class="more">' + TI.t('g_more', { n: shots.length - 4 }) + '</span>' : '';
      return '<button type="button" class="g-thumb" data-thumb="' + i + '" aria-label="' + TI.esc(TI.t('g_thumb', { i: i + 1, c: cap(i) })) + '"><img src="' + sh.src + '" alt="" loading="lazy">' + extra + '</button>';
    }).join('');
    var papers = TI.papers.map(function (pa) {
      var yes = p.papers.indexOf(pa.id) > -1;
      return '<li class="' + (yes ? 'yes' : 'no') + '">' + TI.icon(yes ? 'check' : 'minus') + '<span>' + TI.esc(TI.L(pa)) + '</span><small>' + TI.t(yes ? 'paper_yes' : 'paper_no') + '</small></li>';
    }).join('');
    var amen = (p.amen || []).map(function (id) {
      var a = TI.amenities.filter(function (x) { return x.id === id; })[0];
      return a ? '<li>' + TI.icon('check') + TI.esc(TI.L(a)) + '</li>' : '';
    }).join('');
    var mapUrl = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent((c ? c.fr : '') + ', ' + (w ? w.fr : '') + ', Algérie');

    root.innerHTML =
      '<div class="container property-top">' +
        '<nav class="breadcrumb" aria-label="' + TI.t('bc_label') + '"><ol><li><a href="index.html">' + TI.t('bc_home') + '</a>' + TI.icon('chev', 'flip') + '</li>' +
          '<li><a href="biens.html?tx=' + p.tx + '">' + TI.t(p.tx === 'vente' ? 'a_title_vente' : 'a_title_location') + '</a>' + TI.icon('chev', 'flip') + '</li>' +
          '<li><a href="biens.html?wilaya=' + p.wilaya + '">' + TI.esc(TI.L(w)) + '</a>' + TI.icon('chev', 'flip') + '</li>' +
          '<li aria-current="page" class="ltr">' + p.ref + '</li></ol></nav>' +
        '<section class="gallery" aria-label="' + TI.t('g_label') + '">' +
          '<div class="g-main" id="g-main">' +
            '<button type="button" class="g-open" id="g-open" aria-label="' + TI.t('g_open') + '"><img id="g-img" alt="" width="800" height="600" draggable="false"></button>' +
            '<div class="g-badges">' + TI.badges(p) + '</div>' +
            '<button type="button" class="g-nav g-prev" data-g="-1" aria-label="' + TI.t('g_prev') + '">' + TI.icon('chev', 'rev') + '</button>' +
            '<button type="button" class="g-nav g-next" data-g="1" aria-label="' + TI.t('g_next') + '">' + TI.icon('chev', 'flip') + '</button>' +
            '<span class="g-caption" id="g-caption"></span><span class="g-counter ltr" id="g-counter" aria-live="polite"></span>' +
          '</div>' +
          '<div class="g-thumbs">' + thumbs + '</div>' +
        '</section>' +
      '</div>' +
      '<div class="container p-layout">' +
        '<div class="p-main">' +
          '<header class="p-head">' +
            '<p class="p-ref">' + TI.t('ref') + ' <span class="ltr">' + p.ref + '</span></p>' +
            '<h1>' + TI.esc(TI.L(p.title)) + '</h1>' +
            '<p class="p-loc">' + TI.icon('pin') + TI.esc(TI.place(p)) + '</p>' +
            '<p class="p-price">' + pr.main + '<small>' + pr.sub + '</small></p>' +
          '</header>' +
          '<h2 class="sr-only">' + TI.t('p_facts') + '</h2><ul class="p-facts">' + facts() + '</ul>' +
          '<section class="p-sec"><h2>' + TI.t('p_desc') + '</h2><div class="p-desc" id="p-desc"><p>' + TI.esc(TI.L(p.desc)) + '</p></div>' +
            '<button type="button" class="read-more" id="read-more" aria-controls="p-desc" aria-expanded="false" hidden>' + TI.t('read_more') + '</button></section>' +
          '<section class="p-sec"><h2>' + TI.t('p_papers') + '</h2><ul class="papers">' + papers + '</ul><p class="f-hint">' + TI.t('papers_note') + '</p></section>' +
          (amen ? '<section class="p-sec"><h2>' + TI.t('p_amen') + '</h2><ul class="amen-grid">' + amen + '</ul></section>' : '') +
          '<section class="p-sec"><h2>' + TI.t('p_location') + '</h2><div class="loc-card"><img src="' + TI.media.exterior('appartement', p.seed + 3, { sea: w && w.coast, time: 'day' }) + '" alt="" loading="lazy">' +
            '<div><p>' + TI.esc(TI.t('p_location_text', { commune: TI.L(c), wilaya: TI.L(w) })) + '</p><a class="link-arrow" id="map-link" href="' + mapUrl + '" target="_blank" rel="noopener">' + TI.t('p_map') + TI.icon('external', 'ic-sm') + '</a></div></div></section>' +
        '</div>' +
        '<aside class="p-aside" aria-label="' + TI.t('p_agent') + '">' +
          '<div class="agent-card">' +
            '<p class="a-price">' + pr.main + ' <small class="muted">' + pr.sub + '</small></p>' +
            '<div class="agent-actions">' +
              '<a class="btn btn-wa btn-lg" id="agent-wa" href="' + waHref() + '" target="_blank" rel="noopener">' + TI.icon('wa') + TI.t('wa_write') + '</a>' +
              '<div class="row"><a class="btn btn-ghost" href="tel:+' + agent.whatsapp + '">' + TI.icon('phone') + TI.t('call') + '</a><button type="button" class="btn btn-ghost" data-share>' + TI.icon('share') + TI.t('p_share') + '</button></div>' +
              '<button type="button" class="btn btn-primary" data-visit>' + TI.icon('calendar') + TI.t('p_visit') + '</button>' +
            '</div>' +
            '<div class="agent-who"><span class="avatar">' + TI.initials(agent.name) + '</span><div><span>' + TI.t('p_agent') + '</span><b>' + TI.esc(agent.name) + '</b><span>' + TI.t('agent_reply') + '</span></div>' +
              '<button type="button" class="icon-btn" data-fav="' + p.ref + '" aria-pressed="' + TI.favs.has(p.ref) + '" aria-label="' + TI.t(TI.favs.has(p.ref) ? 'fav_remove' : 'fav_add') + '" style="margin-inline-start:auto">' + TI.icon('heart') + '</button></div>' +
          '</div>' +
        '</aside>' +
      '</div>' +
      '<section class="section section-alt"><div class="container"><div class="section-head"><h2>' + TI.t('p_similar') + '</h2><a class="link-arrow" href="biens.html">' + TI.t('see_all') + TI.icon('arrow', 'flip') + '</a></div>' +
        '<div class="card-grid">' + similar().map(TI.card).join('') + '</div></div></section>' +
      '<div class="m-bar" id="m-bar"><a class="btn btn-wa" href="' + waHref() + '" target="_blank" rel="noopener">' + TI.icon('wa') + TI.t('ch_wa_t') + '</a>' +
        '<a class="btn btn-ghost" href="tel:+' + agent.whatsapp + '">' + TI.icon('phone') + TI.t('call') + '</a>' +
        '<button type="button" class="btn btn-primary" data-visit>' + TI.icon('calendar') + '<span>' + TI.t('nav_visit_short') + '</span></button></div>' +
      lightboxHTML() + visitHTML();
    show(idx);
    clampDesc();
    if (visitDone) visitSuccess(visitDone);
  }

  function lightboxHTML() {
    return '<dialog class="lightbox" id="lightbox" aria-label="' + TI.t('g_label') + '"><div class="lb-in">' +
      '<div class="lb-top"><span id="lb-caption-top"></span><span class="ltr" id="lb-counter"></span><button type="button" class="icon-btn lb-close" data-lb-close aria-label="' + TI.t('g_close') + '">' + TI.icon('close') + '</button></div>' +
      '<div class="lb-stage" id="lb-stage"><img id="lb-img" alt="" draggable="false"></div>' +
      '<p class="lb-caption" id="lb-caption"></p>' +
      '<button type="button" class="lb-nav lb-prev" data-g="-1" aria-label="' + TI.t('g_prev') + '">' + TI.icon('chev', 'rev ic-lg') + '</button>' +
      '<button type="button" class="lb-nav lb-next" data-g="1" aria-label="' + TI.t('g_next') + '">' + TI.icon('chev', 'flip ic-lg') + '</button>' +
      '</div></dialog>';
  }
  function visitHTML() {
    var min = TI.forms.isoDate(TI.forms.tomorrow());
    return '<dialog class="modal" id="visit-dialog" aria-labelledby="visit-title"><div class="modal-head"><h2 id="visit-title">' + TI.t('v_title') + '</h2><button type="button" class="icon-btn" data-visit-close aria-label="' + TI.t('v_close') + '">' + TI.icon('close') + '</button></div>' +
      '<div class="modal-body" id="visit-body"><p class="muted">' + TI.t('v_intro') + '</p>' +
      '<p class="badge badge-doc ltr" style="justify-self:start">' + p.ref + ' · ' + TI.esc(TI.L(p.title)) + '</p>' +
      '<form id="visit-form" class="form-grid">' +
        '<div class="form-alert full" data-form-summary hidden role="alert">' + TI.t('err_summary') + '</div>' +
        '<div class="field full"><label for="v-name">' + TI.t('f_name') + '</label><input class="control" id="v-name" name="name" autocomplete="name" required data-rule="name"><p class="f-error" id="v-name-err" hidden></p></div>' +
        '<div class="field full"><label for="v-phone">' + TI.t('f_phone') + '</label><input class="control ltr" id="v-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="' + TI.t('f_phone_ph') + '" required data-rule="phone"><p class="f-error" id="v-phone-err" hidden></p></div>' +
        '<div class="field"><label for="v-date">' + TI.t('v_date') + '</label><input class="control" id="v-date" name="date" type="date" min="' + min + '" required data-rule="visitdate"><p class="f-error" id="v-date-err" hidden></p></div>' +
        '<div class="field"><label for="v-slot">' + TI.t('v_slot') + '</label><select class="control" id="v-slot" name="slot"><option value="am">' + TI.t('slot_am') + '</option><option value="pm">' + TI.t('slot_pm') + '</option></select></div>' +
        '<div class="field full"><label for="v-msg">' + TI.t('f_message') + '</label><textarea class="control" id="v-msg" name="message" rows="3" placeholder="' + TI.t('f_message_ph') + '"></textarea></div>' +
        '<div class="full"><button class="btn btn-primary btn-lg btn-block" type="submit">' + TI.t('v_send') + '</button><p class="demo-note" style="margin-top:8px">' + TI.t('demo_note') + '</p></div>' +
      '</form></div></dialog>';
  }

  function show(i) {
    idx = (i + shots.length) % shots.length;
    var img = TI.$('#g-img', root);
    img.src = shots[idx].src; img.alt = TI.L(p.title) + ' · ' + cap(idx);
    TI.$('#g-counter', root).textContent = (idx + 1) + ' / ' + shots.length;
    TI.$('#g-caption', root).textContent = cap(idx);
    TI.$$('[data-thumb]', root).forEach(function (b) { b.setAttribute('aria-current', String(+b.getAttribute('data-thumb') === idx)); });
    var lb = TI.$('#lightbox', root);
    if (lb && lb.open) {
      var li = TI.$('#lb-img', root); li.src = shots[idx].src; li.alt = img.alt;
      TI.$('#lb-counter', root).textContent = (idx + 1) + ' / ' + shots.length;
      TI.$('#lb-caption', root).textContent = cap(idx);
      TI.$('#lb-caption-top', root).textContent = TI.L(p.title);
    }
  }
  function openLightbox() {
    var lb = TI.$('#lightbox', root);
    if (lb.showModal) lb.showModal(); else lb.setAttribute('open', '');
    show(idx);
    TI.$('[data-lb-close]', lb).focus();
  }
  function closeLightbox() { var lb = TI.$('#lightbox', root); if (lb.open) lb.close(); }
  function step(d) { show(idx + d); }

  function clampDesc() {
    var d = TI.$('#p-desc', root), b = TI.$('#read-more', root);
    d.classList.add('clamped');
    var over = d.scrollHeight > d.clientHeight + 4;
    if (!over) d.classList.remove('clamped');
    b.hidden = !over;
  }

  function openVisit() {
    var dlg = TI.$('#visit-dialog', root);
    if (dlg.showModal) dlg.showModal(); else dlg.setAttribute('open', '');
    var f = TI.$('#visit-name, #v-name', dlg); if (f) f.focus();
  }
  function visitSuccess(d) {
    visitDone = d;
    TI.$('#visit-body', root).innerHTML = '<div class="success-panel" role="status">' + TI.icon('check', 'ic-lg') + '<h3>' + TI.t('v_success_title') + '</h3><p>' +
      TI.esc(TI.t('v_success', { name: d.name, agent: agent.name, phone: d.phone, date: TI.forms.formatDate(d.date) })) + '</p>' +
      '<a class="btn btn-wa" href="' + TI.wa(TI.t('wa_visit', { ref: p.ref, title: TI.L(p.title) }), agent.whatsapp) + '" target="_blank" rel="noopener">' + TI.icon('wa') + TI.t('wa_write') + '</a>' +
      '<button type="button" class="btn btn-ghost" data-visit-close>' + TI.t('v_close') + '</button><p class="demo-note">' + TI.t('demo_note') + '</p></div>';
  }

  function share() {
    var url = location.href.split('#')[0];
    if (navigator.share) {
      navigator.share({ title: TI.L(p.title), text: TI.waProperty(p), url: url }).catch(function () {});
      return;
    }
    var done = function () { TI.toast(TI.t('share_copied')); };
    var fail = function () { TI.toast(TI.t('share_fail')); };
    if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(url).then(done, fail);
    else fail();
  }

  /* Events (delegated on root so they survive re-renders) */
  root.addEventListener('click', function (e) {
    var t = e.target;
    var g = t.closest('[data-g]'); if (g) { step(+g.getAttribute('data-g')); return; }
    var th = t.closest('[data-thumb]');
    if (th) { var i = +th.getAttribute('data-thumb'); show(i); if (i === 3 && shots.length > 4 && window.innerWidth >= 900) openLightbox(); return; }
    if (t.closest('#g-open')) { if (swiped) { swiped = false; return; } openLightbox(); return; }
    if (t.closest('[data-lb-close]')) { closeLightbox(); return; }
    if (t.id === 'lightbox') { closeLightbox(); return; }
    if (t.closest('#read-more')) {
      var d = TI.$('#p-desc', root), open = d.classList.toggle('clamped') === false;
      t.closest('#read-more').setAttribute('aria-expanded', String(open));
      t.closest('#read-more').textContent = TI.t(open ? 'read_less' : 'read_more');
      return;
    }
    if (t.closest('[data-visit]')) { openVisit(); return; }
    if (t.closest('[data-visit-close]') || t.id === 'visit-dialog') { TI.$('#visit-dialog', root).close(); return; }
    if (t.closest('[data-share]')) { share(); return; }
  });
  root.addEventListener('keydown', function (e) {
    var lb = TI.$('#lightbox', root);
    var inGallery = (lb && lb.open) || (e.target.closest && e.target.closest('.gallery'));
    if (!inGallery) return;
    if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
      e.preventDefault();
      var forward = (e.key === 'ArrowRight') !== TI.isRTL();
      step(forward ? 1 : -1);
    }
  });
  /* Swipe on the main photo and in the lightbox */
  var sx = null, swiped = false;
  root.addEventListener('pointerdown', function (e) { swiped = false; if (e.target.closest('#g-main, #lb-stage')) sx = e.clientX; });
  root.addEventListener('dragstart', function (e) { if (e.target.closest('.gallery, #lb-stage')) e.preventDefault(); });
  root.addEventListener('pointercancel', function () { sx = null; });
  root.addEventListener('pointerup', function (e) {
    if (sx == null) return;
    var dx = e.clientX - sx; sx = null;
    if (Math.abs(dx) < 40) return;
    var forward = (dx < 0) !== TI.isRTL();
    swiped = true;
    step(forward ? 1 : -1);
  });

  render();
  var bindVisit = function () { var f = TI.$('#visit-form', root); if (f && !f._bound) { f._bound = true; TI.forms.bind(f, visitSuccess); } };
  bindVisit();
  window.addEventListener('resize', TI.debounce(clampDesc, 200));
  return { render: function () { render(); bindVisit(); } };
};
