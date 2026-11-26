/* Home: hero search, wilayas, featured listings, property types, new developments. */
window.TI = window.TI || {};
TI.pages = TI.pages || {};
TI.pages.home = function () {
  var panel = TI.searchPanel(TI.$('#hero-search'));
  var hero = TI.$('#hero-bg'); if (hero) hero.src = TI.media.hero();
  var times = ['day', 'dusk', 'sunset', 'day', 'dusk'];

  function render() {
    document.title = TI.t('meta_home');
    var counts = {};
    TI.properties.forEach(function (p) { counts[p.wilaya] = (counts[p.wilaya] || 0) + 1; });
    var wil = TI.wilayas.slice().sort(function (a, b) { return (counts[b.code] || 0) - (counts[a.code] || 0); }).slice(0, 5);
    TI.$('#wilaya-grid').innerHTML = wil.map(function (w, i) {
      var img = TI.media.exterior(i % 2 ? 'appartement' : 'villa', w.code * 13 + 5, { sea: w.coast, time: times[i] });
      return '<a class="wilaya-tile" href="biens.html?wilaya=' + w.code + '"><img src="' + img + '" alt="" loading="lazy">' +
        '<span class="wt-in"><span class="wt-name">' + TI.esc(TI.L(w)) + '</span><span class="wt-count">' + TI.plural('wil_count_', counts[w.code] || 0) + '</span></span></a>';
    }).join('');

    TI.$('#featured-grid').innerHTML = TI.properties.filter(function (p) { return p.featured; }).slice(0, 6).map(TI.card).join('');

    TI.$('#type-grid').innerHTML = TI.types.map(function (t) {
      var n = TI.properties.filter(function (p) { return p.type === t.id; }).length;
      return '<a class="type-tile" href="biens.html?type=' + t.id + '">' + TI.icon(t.icon) + '<b>' + TI.esc(TI.L(t.plural)) + '</b><span>' + TI.plural('type_count_', n) + '</span></a>';
    }).join('');

    TI.$('#new-grid').innerHTML = TI.properties.filter(function (p) { return p.isNew; }).slice(0, 3).map(TI.card).join('');
    TI.$('#sell-wa').href = TI.wa(TI.t('wa_sell'));
  }
  render();
  return { render: function () { render(); panel.render(); } };
};
