/* Properties archive: filter sidebar (sheet on mobile), active chips, sort, grid/list, pagination, WhatsApp alert.
   All state lives in the URL, so any search can be shared or bookmarked. */
window.TI = window.TI || {};
TI.pages = TI.pages || {};
TI.pages.archive = function () {
  var root = TI.$('#archive');
  var s = TI.filters.fromURL();
  var F = TI.filters;

  function seg(v, key) {
    var on = v === 'neuf' ? s.neuf : (!s.neuf && s.tx === v);
    return '<label><input type="radio" name="tx" value="' + v + '"' + (on ? ' checked' : '') + '><span>' + TI.t(key) + '</span></label>';
  }
  function chk(name, id, label, on, chip) {
    if (chip) return '<label class="chip-check"><input type="checkbox" name="' + name + '" value="' + id + '"' + (on ? ' checked' : '') + '><span>' + label + '</span></label>';
    return '<label class="check"><input type="checkbox" name="' + name + '" value="' + id + '"' + (on ? ' checked' : '') + '><span>' + label + '</span></label>';
  }
  function formHTML() {
    return '<div class="f-group"><span class="field-label" id="fl-tx">' + TI.t('f_tx') + '</span><div class="seg" role="radiogroup" aria-labelledby="fl-tx">' +
        seg('', 'f_all') + seg('vente', 's_buy') + seg('location', 's_rent') + seg('neuf', 's_new') + '</div></div>' +
      '<div class="f-group">' +
        '<div class="field"><label for="ff-wilaya">' + TI.t('s_wilaya') + '</label><select class="control" id="ff-wilaya" name="wilaya">' + TI.opts.wilayas(s.wilaya) + '</select></div>' +
        '<div class="field"><label for="ff-commune">' + TI.t('s_commune') + '</label><select class="control" id="ff-commune" name="commune"' + (s.wilaya ? '' : ' disabled') + '>' + TI.opts.communes(s.wilaya, s.commune) + '</select></div>' +
        '<div class="field"><label for="ff-type">' + TI.t('s_type') + '</label><select class="control" id="ff-type" name="type">' + TI.opts.types(s.type) + '</select></div>' +
      '</div>' +
      '<div class="f-group"><span class="field-label">' + TI.t('s_typo') + '</span><div class="chips-wrap">' +
        F.typos.map(function (t) { return chk('typo', t, t, s.typo.indexOf(t) > -1, true); }).join('') + '</div></div>' +
      '<div class="f-group"><span class="field-label">' + TI.t('f_price') + '</span><div class="range">' +
        '<div class="field"><label class="sr-only" for="ff-pmin">' + TI.t('f_price') + ' ' + TI.t('f_min') + '</label><input class="control ltr" id="ff-pmin" name="pmin" inputmode="numeric" autocomplete="off" placeholder="' + TI.t('f_min') + '" value="' + (s.pmin ? TI.num(s.pmin) : '') + '"></div>' +
        '<div class="field"><label class="sr-only" for="ff-pmax">' + TI.t('f_price') + ' ' + TI.t('f_max') + '</label><input class="control ltr" id="ff-pmax" name="pmax" inputmode="numeric" autocomplete="off" placeholder="' + TI.t('f_max') + '" value="' + (s.pmax ? TI.num(s.pmax) : '') + '"></div>' +
      '</div>' +
        '<div class="field"><label for="ff-smin">' + TI.t('f_surface') + '</label><input class="control ltr" id="ff-smin" name="smin" inputmode="numeric" autocomplete="off" value="' + (s.smin || '') + '"></div></div>' +
      '<div class="f-group"><span class="field-label">' + TI.t('f_papers') + '</span><div class="checks">' +
        TI.papers.slice(0, 3).map(function (p) { return chk('papers', p.id, TI.esc(TI.L(p)), s.papers.indexOf(p.id) > -1); }).join('') + '</div></div>' +
      '<div class="f-group"><span class="field-label">' + TI.t('f_amen') + '</span><div class="chips-wrap">' +
        TI.amenities.filter(function (a) { return a.filter; }).map(function (a) { return chk('amen', a.id, TI.esc(TI.L(a)), s.amen.indexOf(a.id) > -1, true); }).join('') + '</div></div>';
  }

  function shell() {
    var vt = function (v, ic, key) { return '<button type="button" data-view="' + v + '" aria-pressed="' + (s.view === v) + '" aria-label="' + TI.t(key) + '" title="' + TI.t(key) + '">' + TI.icon(ic) + '</button>'; };
    root.innerHTML =
      '<div class="page-head"><div class="container">' +
        '<nav class="breadcrumb" aria-label="' + TI.t('bc_label') + '"><ol><li><a href="index.html">' + TI.t('bc_home') + '</a>' + TI.icon('chev', 'flip') + '</li><li aria-current="page">' + TI.t('bc_props') + '</li></ol></nav>' +
        '<h1 id="a-title"></h1><p class="result-count" id="a-count" role="status" aria-live="polite"></p>' +
      '</div></div>' +
      '<div class="container archive">' +
        '<aside class="filters" id="filters" aria-labelledby="filters-title">' +
          '<div class="filters-head"><h2 id="filters-title">' + TI.t('f_title') + '</h2>' +
            '<button type="button" class="filters-reset" data-reset>' + TI.t('f_reset') + '</button>' +
            '<button type="button" class="icon-btn filters-close" data-sheet-close aria-label="' + TI.t('f_close') + '">' + TI.icon('close') + '</button></div>' +
          '<form class="filters-body" id="filter-form" novalidate>' + formHTML() + '</form>' +
          '<div class="filters-foot"><button type="button" class="filters-reset" data-reset>' + TI.t('f_reset') + '</button><button type="button" class="btn btn-primary" data-sheet-close id="filters-apply"></button></div>' +
        '</aside>' +
        '<section class="results-col" aria-labelledby="a-title">' +
          '<div class="toolbar">' +
            '<button type="button" class="btn btn-ghost btn-sm filter-open" id="filter-open" aria-controls="filters" aria-expanded="false">' + TI.icon('filter') + '<span id="filter-open-label"></span></button>' +
            '<div class="toolbar-end"><label class="sr-only" for="sort">' + TI.t('sort_label') + '</label><select class="control" id="sort">' +
              F.sorts.map(function (k) { return '<option value="' + k + '"' + (s.sort === k ? ' selected' : '') + '>' + TI.t('sort_' + k) + '</option>'; }).join('') + '</select>' +
              '<div class="view-toggle" role="group" aria-label="' + TI.t('view_label') + '">' + vt('grid', 'grid', 'view_grid') + vt('list', 'list', 'view_list') + '</div></div>' +
          '</div>' +
          '<div class="active-chips" id="chips"></div>' +
          '<div id="results" class="card-grid"></div>' +
          '<div id="empty" hidden></div>' +
          '<nav class="pagination" id="pagination" aria-label="' + TI.t('page_label') + '"></nav>' +
          '<div class="alert-box" id="alert">' + TI.icon('wa', 'ic-lg') + '<div><h3>' + TI.t('alert_title') + '</h3><p>' + TI.t('alert_text') + '</p></div><a class="btn btn-wa" id="alert-btn" target="_blank" rel="noopener">' + TI.t('alert_btn') + '</a></div>' +
        '</section>' +
      '</div>';
    ['ff-pmin', 'ff-pmax'].forEach(function (id) { TI.bindMoneyInput(TI.$('#' + id, root)); });
  }

  function readForm() {
    var f = TI.$('#filter-form', root), el = f.elements;
    var tx = (TI.$('input[name="tx"]:checked', f) || {}).value || '';
    s.neuf = tx === 'neuf'; s.tx = tx === 'neuf' ? '' : tx;
    s.wilaya = el.wilaya.value; s.commune = el.commune.value; s.type = el.type.value;
    s.typo = TI.$$('input[name="typo"]:checked', f).map(function (i) { return i.value; });
    s.pmin = TI.digits(el.pmin.value); s.pmax = TI.digits(el.pmax.value); s.smin = TI.digits(el.smin.value);
    s.papers = TI.$$('input[name="papers"]:checked', f).map(function (i) { return i.value; });
    s.amen = TI.$$('input[name="amen"]:checked', f).map(function (i) { return i.value; });
  }

  function title() {
    var key = s.favoris ? 'a_title_fav' : s.neuf ? 'a_title_neuf' : s.tx ? 'a_title_' + s.tx : 'a_title_all';
    var t = TI.t(key), w = s.wilaya && TI.getWilaya(s.wilaya);
    return w ? TI.t('a_in', { t: t, w: TI.L(w) }) : t;
  }

  function chips() {
    var c = [];
    var add = function (label, fn) { c.push({ label: label, fn: fn }); };
    if (s.favoris) add(TI.t('chip_fav'), function () { s.favoris = false; });
    if (s.neuf) add(TI.t('chip_new'), function () { s.neuf = false; });
    if (s.tx) add(TI.t('tx_' + s.tx), function () { s.tx = ''; });
    if (s.wilaya) add(TI.L(TI.getWilaya(s.wilaya)), function () { s.wilaya = ''; s.commune = ''; });
    if (s.commune) add(TI.L(TI.getCommune(s.wilaya, s.commune)), function () { s.commune = ''; });
    if (s.type) add(TI.L(TI.getType(s.type)), function () { s.type = ''; });
    s.typo.forEach(function (t) { add(t, function () { s.typo = s.typo.filter(function (x) { return x !== t; }); }); });
    if (s.pmin) add(TI.t('chip_budget_min', { n: TI.num(s.pmin) }), function () { s.pmin = 0; });
    if (s.pmax) add(TI.t('chip_budget_max', { n: TI.num(s.pmax) }), function () { s.pmax = 0; });
    if (s.smin) add(TI.t('chip_surface', { n: TI.num(s.smin) }), function () { s.smin = 0; });
    s.papers.forEach(function (id) { add(TI.L(TI.papers.filter(function (p) { return p.id === id; })[0]), function () { s.papers = s.papers.filter(function (x) { return x !== id; }); }); });
    s.amen.forEach(function (id) { add(TI.L(TI.amenities.filter(function (a) { return a.id === id; })[0]), function () { s.amen = s.amen.filter(function (x) { return x !== id; }); }); });
    return c;
  }
  var chipList = [];

  /* Only touch the DOM when the markup really changes, so a click that also blurs an input
     (and fires "change") still lands on the button it started on. */
  function setHTML(el, html) { if (el._html !== html) { el.innerHTML = html; el._html = html; } }

  function results() {
    var all = F.apply(TI.properties, s), n = all.length, per = F.perPage;
    var pages = Math.max(1, Math.ceil(n / per));
    if (s.page > pages) s.page = pages;
    var shown = all.slice((s.page - 1) * per, s.page * per);
    var t = title();
    TI.$('#a-title', root).textContent = t;
    document.title = t + TI.t('meta_suffix');
    TI.$('#a-count', root).textContent = TI.plural('a_count_', n);
    TI.$('#filters-apply', root).textContent = TI.plural('f_apply_', n);
    var ac = F.activeCount(s);
    TI.$('#filter-open-label', root).textContent = ac ? TI.t('f_open_n', { n: ac }) : TI.t('f_open');

    chipList = chips();
    setHTML(TI.$('#chips', root), chipList.map(function (c, i) {
      return '<button type="button" class="active-chip" data-chip="' + i + '" aria-label="' + TI.esc(TI.t('chip_remove', { f: c.label })) + '">' + TI.esc(c.label) + TI.icon('close') + '</button>';
    }).join('') + (chipList.length > 1 ? '<button type="button" class="clear-all" data-reset>' + TI.t('clear_all') + '</button>' : ''));
    TI.$('#chips', root).hidden = !chipList.length;

    var grid = TI.$('#results', root);
    grid.className = 'card-grid' + (s.view === 'list' ? ' list' : '');
    setHTML(grid, shown.map(TI.card).join(''));
    grid.hidden = !n;

    var empty = TI.$('#empty', root);
    empty.hidden = !!n;
    if (!n) {
      empty.className = 'empty';
      setHTML(empty, s.favoris && F.activeCount(s) === 0
        ? TI.icon('heart') + '<h2>' + TI.t('empty_fav_title') + '</h2><p>' + TI.t('empty_fav') + '</p><a class="btn btn-primary" href="biens.html">' + TI.t('all_props') + '</a>'
        : TI.icon('search') + '<h2>' + TI.t('empty_title') + '</h2><p>' + TI.t('empty_text') + '</p><button type="button" class="btn btn-primary" data-reset>' + TI.t('f_reset') + '</button>');
    }

    var pg = TI.$('#pagination', root);
    pg.hidden = pages < 2;
    if (pages > 1) {
      var h = '<button type="button" data-goto="' + (s.page - 1) + '"' + (s.page === 1 ? ' disabled' : '') + ' aria-label="' + TI.t('page_prev') + '">' + TI.icon('chev', 'rev') + '</button>';
      for (var i = 1; i <= pages; i++) h += '<button type="button" data-goto="' + i + '"' + (i === s.page ? ' aria-current="page"' : '') + ' aria-label="' + TI.t('page_n', { n: i }) + '">' + i + '</button>';
      h += '<button type="button" data-goto="' + (s.page + 1) + '"' + (s.page === pages ? ' disabled' : '') + ' aria-label="' + TI.t('page_next') + '">' + TI.icon('chev', 'flip') + '</button>';
      setHTML(pg, h);
    } else setHTML(pg, '');

    var q = [title()].concat(chipList.filter(function (c) { return c.label !== TI.L(TI.getWilaya(s.wilaya) || {}); }).map(function (c) { return c.label; })).join(', ');
    TI.$('#alert-btn', root).href = TI.wa(TI.t('wa_alert', { q: q }));
    TI.$('#alert', root).hidden = s.favoris;

    TI.$$('.view-toggle button', root).forEach(function (b) { b.setAttribute('aria-pressed', String(b.getAttribute('data-view') === s.view)); });
    F.toURL(s);
  }

  function rebuildForm() { TI.$('#filter-form', root).innerHTML = formHTML(); ['ff-pmin', 'ff-pmax'].forEach(function (id) { TI.bindMoneyInput(TI.$('#' + id, root)); }); }

  /* Sheet (mobile) */
  var sheetOpener = null;
  function openSheet() {
    sheetOpener = document.activeElement;
    TI.$('#filters', root).classList.add('open'); document.body.classList.add('no-scroll');
    TI.$('#filter-open', root).setAttribute('aria-expanded', 'true');
    var c = TI.$('.filters-close', root); if (c) c.focus();
  }
  function closeSheet() {
    var f = TI.$('#filters', root); if (!f.classList.contains('open')) return;
    f.classList.remove('open'); document.body.classList.remove('no-scroll');
    TI.$('#filter-open', root).setAttribute('aria-expanded', 'false');
    if (sheetOpener && sheetOpener.focus) sheetOpener.focus();
  }

  function bind() {
    var onForm = function (e) {
      readForm();
      if (e.target.name === 'wilaya') {
        s.commune = '';
        var c = TI.$('#ff-commune', root); c.innerHTML = TI.opts.communes(s.wilaya, ''); c.disabled = !s.wilaya;
      }
      s.page = 1; results();
    };
    root.addEventListener('change', function (e) {
      if (e.target.closest('#filter-form')) onForm(e);
      if (e.target.id === 'sort') { s.sort = e.target.value; s.page = 1; results(); }
    });
    root.addEventListener('input', TI.debounce(function (e) {
      if (e.target.closest('#filter-form') && /^(pmin|pmax|smin)$/.test(e.target.name)) onForm(e);
    }, 300));
    root.addEventListener('submit', function (e) { e.preventDefault(); closeSheet(); });
    root.addEventListener('click', function (e) {
      var t = e.target;
      if (t.closest('[data-reset]')) {
        var fav = s.favoris; s = F.empty(); s.favoris = fav;
        rebuildForm(); results(); return;
      }
      var chip = t.closest('[data-chip]');
      if (chip) { chipList[+chip.getAttribute('data-chip')].fn(); s.page = 1; rebuildForm(); results(); var nx = TI.$('[data-chip]', root) || TI.$('#sort', root); if (nx) nx.focus(); return; }
      var v = t.closest('[data-view]');
      if (v) { s.view = v.getAttribute('data-view'); results(); return; }
      var pgb = t.closest('[data-goto]');
      if (pgb && !pgb.disabled) {
        s.page = +pgb.getAttribute('data-goto'); results();
        TI.$('.results-col', root).scrollIntoView({ block: 'start' });
        var cur = TI.$('#pagination [aria-current="page"]', root); if (cur) cur.focus();
        return;
      }
      if (t.closest('#filter-open')) { openSheet(); return; }
      if (t.closest('[data-sheet-close]')) { closeSheet(); return; }
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeSheet(); });
    document.addEventListener('ti:favs', function () { if (s.favoris) results(); });
  }

  shell(); bind(); results();
  return { render: function () { readForm(); shell(); results(); } };
};
