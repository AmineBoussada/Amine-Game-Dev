/* Search state <-> URL, matching and sorting, plus the home-page search panel. */
window.TI = window.TI || {};
(function (TI) {
  function list(v) { return v ? v.split(',').filter(Boolean) : []; }
  var TYPOS = ['F1', 'F2', 'F3', 'F4', 'F5+'];
  var SORTS = ['recent', 'price_asc', 'price_desc', 'surface'];

  TI.filters = {
    typos: TYPOS, sorts: SORTS, perPage: 12,
    empty: function () { return { tx: '', neuf: false, wilaya: '', commune: '', type: '', typo: [], pmin: 0, pmax: 0, smin: 0, papers: [], amen: [], favoris: false, sort: 'recent', view: 'grid', page: 1 }; },
    fromURL: function () {
      var p = TI.params(), s = TI.filters.empty();
      if (['vente', 'location'].indexOf(p.get('tx')) > -1) s.tx = p.get('tx');
      s.neuf = p.get('neuf') === '1';
      if (s.neuf) s.tx = '';
      if (p.get('wilaya') && TI.getWilaya(p.get('wilaya'))) s.wilaya = String(Number(p.get('wilaya')));
      if (s.wilaya && p.get('commune') && TI.getCommune(s.wilaya, p.get('commune'))) s.commune = p.get('commune');
      if (p.get('type') && TI.getType(p.get('type'))) s.type = p.get('type');
      s.typo = list(p.get('typo')).filter(function (t) { return TYPOS.indexOf(t) > -1; });
      s.pmin = TI.digits(p.get('pmin')); s.pmax = TI.digits(p.get('pmax')); s.smin = TI.digits(p.get('smin'));
      s.papers = list(p.get('papers')).filter(function (x) { return TI.papers.some(function (q) { return q.id === x; }); });
      s.amen = list(p.get('amen')).filter(function (x) { return TI.amenities.some(function (q) { return q.id === x; }); });
      s.favoris = p.get('favoris') === '1';
      if (SORTS.indexOf(p.get('sort')) > -1) s.sort = p.get('sort');
      s.view = p.get('view') === 'list' ? 'list' : 'grid';
      s.page = Math.max(1, TI.digits(p.get('page')) || 1);
      return s;
    },
    toQuery: function (s, keepPaging) {
      var p = new URLSearchParams();
      if (s.favoris) p.set('favoris', '1');
      if (s.neuf) p.set('neuf', '1'); else if (s.tx) p.set('tx', s.tx);
      if (s.wilaya) p.set('wilaya', s.wilaya);
      if (s.commune) p.set('commune', s.commune);
      if (s.type) p.set('type', s.type);
      if (s.typo.length) p.set('typo', s.typo.join(','));
      if (s.pmin) p.set('pmin', s.pmin);
      if (s.pmax) p.set('pmax', s.pmax);
      if (s.smin) p.set('smin', s.smin);
      if (s.papers.length) p.set('papers', s.papers.join(','));
      if (s.amen.length) p.set('amen', s.amen.join(','));
      if (keepPaging !== false) {
        if (s.sort !== 'recent') p.set('sort', s.sort);
        if (s.view === 'list') p.set('view', 'list');
        if (s.page > 1) p.set('page', s.page);
      }
      return p.toString();
    },
    toURL: function (s) { var q = TI.filters.toQuery(s); history.replaceState(null, '', location.pathname + (q ? '?' + q : '')); },
    match: function (p, s) {
      if (s.favoris && !TI.favs.has(p.ref)) return false;
      if (s.neuf && !p.isNew) return false;
      if (s.tx && p.tx !== s.tx) return false;
      if (s.wilaya && String(p.wilaya) !== String(s.wilaya)) return false;
      if (s.commune && p.commune !== s.commune) return false;
      if (s.type && p.type !== s.type) return false;
      if (s.typo.length) {
        if (!p.typo) return false;
        var ok = s.typo.indexOf(p.typo) > -1 || (s.typo.indexOf('F5+') > -1 && (p.rooms || 0) >= 5);
        if (!ok) return false;
      }
      if (s.pmin && p.price < s.pmin) return false;
      if (s.pmax && p.price > s.pmax) return false;
      if (s.smin && p.surface < s.smin) return false;
      for (var i = 0; i < s.papers.length; i++) if (p.papers.indexOf(s.papers[i]) < 0) return false;
      for (var j = 0; j < s.amen.length; j++) if ((p.amen || []).indexOf(s.amen[j]) < 0) return false;
      return true;
    },
    apply: function (all, s) {
      var r = all.filter(function (p) { return TI.filters.match(p, s); });
      var by = {
        recent: function (a, b) { return a.date < b.date ? 1 : a.date > b.date ? -1 : 0; },
        price_asc: function (a, b) { return a.price - b.price; },
        price_desc: function (a, b) { return b.price - a.price; },
        surface: function (a, b) { return b.surface - a.surface; }
      }[s.sort] || function () { return 0; };
      return r.sort(by);
    },
    count: function (s) { return TI.filters.apply(TI.properties, s).length; },
    /* Number of narrowing filters in use (for the "Filtres (n)" button). */
    activeCount: function (s) {
      return (s.tx || s.neuf ? 1 : 0) + (s.wilaya ? 1 : 0) + (s.commune ? 1 : 0) + (s.type ? 1 : 0) + s.typo.length +
        (s.pmin ? 1 : 0) + (s.pmax ? 1 : 0) + (s.smin ? 1 : 0) + s.papers.length + s.amen.length;
    }
  };

  /* Option helpers shared by the home panel and the archive filters. */
  TI.opts = {
    wilayas: function (sel) {
      return '<option value="">' + TI.t('s_all_wilayas') + '</option>' + TI.wilayas.map(function (w) {
        return '<option value="' + w.code + '"' + (String(sel) === String(w.code) ? ' selected' : '') + '>' + (w.code < 10 ? '0' : '') + w.code + ' · ' + TI.esc(TI.L(w)) + '</option>';
      }).join('');
    },
    communes: function (wcode, sel) {
      var w = TI.getWilaya(wcode);
      return '<option value="">' + TI.t('s_all_communes') + '</option>' + (w ? w.communes.map(function (c) {
        return '<option value="' + c.id + '"' + (sel === c.id ? ' selected' : '') + '>' + TI.esc(TI.L(c)) + '</option>';
      }).join('') : '');
    },
    types: function (sel) {
      return '<option value="">' + TI.t('s_all_types') + '</option>' + TI.types.map(function (t) {
        return '<option value="' + t.id + '"' + (sel === t.id ? ' selected' : '') + '>' + TI.esc(TI.L(t)) + '</option>';
      }).join('');
    }
  };

  /* Format a budget input as the user types: 25000000 -> 25 000 000 (FR) / 25.000.000 (AR). */
  TI.bindMoneyInput = function (input) {
    input.addEventListener('input', function () {
      var n = TI.digits(input.value);
      input.value = n ? TI.num(n) : '';
    });
  };

  /* ---------- Home search panel ---------- */
  TI.searchPanel = function (host) {
    var s = TI.filters.empty();
    var expanded = false;
    function render() {
      host.className = 'search-panel' + (expanded ? ' expanded' : '');
      host.innerHTML =
        '<form id="home-search" role="search" aria-label="' + TI.t('s_label') + '" novalidate>' +
          '<div class="tabs" role="group" aria-label="' + TI.t('f_tx') + '">' +
            tab('vente', 's_buy') + tab('location', 's_rent') + tab('neuf', 's_new') +
          '</div>' +
          '<div class="s-fields">' +
            '<div class="field"><label for="hs-wilaya">' + TI.t('s_wilaya') + '</label><select class="control" id="hs-wilaya" name="wilaya">' + TI.opts.wilayas(s.wilaya) + '</select></div>' +
            '<div class="field s-extra"><label for="hs-commune">' + TI.t('s_commune') + '</label><select class="control" id="hs-commune" name="commune"' + (s.wilaya ? '' : ' disabled') + '>' + TI.opts.communes(s.wilaya, s.commune) + '</select></div>' +
            '<div class="field"><label for="hs-type">' + TI.t('s_type') + '</label><select class="control" id="hs-type" name="type">' + TI.opts.types(s.type) + '</select></div>' +
            '<div class="field s-extra"><label for="hs-typo">' + TI.t('s_typo') + '</label><select class="control" id="hs-typo" name="typo"><option value="">' + TI.t('s_any') + '</option>' +
              TYPOS.map(function (t) { return '<option' + (s.typo[0] === t ? ' selected' : '') + '>' + t + '</option>'; }).join('') + '</select></div>' +
            '<div class="field s-extra"><label for="hs-budget">' + TI.t('s_budget') + '</label><input class="control ltr" id="hs-budget" name="pmax" inputmode="numeric" autocomplete="off" placeholder="' + TI.t('s_budget_ph') + '" value="' + (s.pmax ? TI.num(s.pmax) : '') + '"></div>' +
            '<div class="s-go-wrap"><button class="btn btn-primary btn-lg s-go" type="submit" id="hs-go">' + TI.icon('search') + '<span id="hs-go-label"></span></button></div>' +
          '</div>' +
          '<button type="button" class="s-more" id="hs-more" aria-expanded="' + expanded + '">' + TI.t(expanded ? 's_less' : 's_more') + '</button>' +
        '</form>';
      TI.bindMoneyInput(TI.$('#hs-budget', host));
      updateCount();
    }
    function tab(v, k) {
      var on = v === 'neuf' ? s.neuf : (!s.neuf && s.tx === v);
      return '<button type="button" class="tab" data-tx="' + v + '" aria-pressed="' + on + '">' + TI.t(k) + '</button>';
    }
    function read() {
      var f = TI.$('#home-search', host).elements;
      s.wilaya = f.wilaya.value; s.commune = f.commune.value; s.type = f.type.value;
      s.typo = f.typo.value ? [f.typo.value] : []; s.pmax = TI.digits(f.pmax.value);
    }
    function updateCount() {
      var n = TI.filters.count(s);
      TI.$('#hs-go-label', host).textContent = TI.plural('s_go_', n);
    }
    s.tx = 'vente';
    host.addEventListener('click', function (e) {
      var tb = e.target.closest('.tab');
      if (tb) {
        var v = tb.getAttribute('data-tx');
        s.neuf = v === 'neuf'; s.tx = v === 'neuf' ? '' : v;
        TI.$$('.tab', host).forEach(function (b) { b.setAttribute('aria-pressed', String(b === tb)); });
        updateCount();
      }
      if (e.target.closest('#hs-more')) { expanded = !expanded; read(); render(); TI.$('#hs-more', host).focus(); }
    });
    host.addEventListener('change', function (e) {
      read();
      if (e.target.name === 'wilaya') {
        s.commune = '';
        var c = TI.$('#hs-commune', host);
        c.innerHTML = TI.opts.communes(s.wilaya, '');
        c.disabled = !s.wilaya;
      }
      updateCount();
    });
    host.addEventListener('input', TI.debounce(function (e) { if (e.target.name === 'pmax') { read(); updateCount(); } }, 200));
    host.addEventListener('submit', function (e) {
      e.preventDefault(); read();
      location.href = 'biens.html' + (TI.filters.toQuery(s, false) ? '?' + TI.filters.toQuery(s, false) : '');
    });
    render();
    return { render: function () { if (TI.$('#home-search', host)) read(); render(); } };
  };
})(window.TI);
