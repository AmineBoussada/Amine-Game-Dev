/* Liste d'annonces + carte Leaflet synchronisées (survol/clic des deux côtés). */
(function () {
  'use strict';
  if (typeof novaImmo === 'undefined') return;

  var section = document.querySelector('.nova-listings');
  if (!section) return;
  var grid = section.querySelector('.nova-grid');
  var layout = section.querySelector('.nova-listings__layout');
  var form = section.querySelector('.nova-filters');
  var mapToggle = section.querySelector('.nova-map-toggle');
  var mapEl = document.getElementById('nova-map');
  var map = null, markers = {};

  function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

  function cardHtml(l) {
    var img = l.image
      ? '<img class="nova-card__img" src="' + esc(l.image) + '" alt="' + esc(l.title) + '" loading="lazy" width="720" height="960">'
      : '<span class="nova-card__img nova-card__img--empty" aria-hidden="true"></span>';
    var tx = l.transaction === 'location' ? 'Location' : 'Vente';
    return '<article class="nova-card" data-id="' + l.id + '" data-lat="' + l.lat + '" data-lng="' + l.lng + '" tabindex="0">' +
      '<a class="nova-card__link" href="' + esc(l.url) + '" aria-label="' + esc(l.title) + '">' + img + '</a>' +
      '<div class="nova-card__top"><span class="nova-chip">' + esc(l.type) + '</span><span class="nova-chip nova-chip--tx">' + tx + '</span>' +
      '<button class="nova-card__fav" type="button" aria-pressed="false" data-fav="' + l.id + '">♥</button></div>' +
      '<div class="nova-card__body"><p class="nova-card__price">' + esc(l.prix_label) + '</p>' +
      '<h3 class="nova-card__title"><a href="' + esc(l.url) + '">' + esc(l.title) + '</a></h3>' +
      '<div class="nova-card__meta"><span class="nova-card__loc">📍 ' + esc(l.wilaya) + '</span><span class="nova-card__date">' + esc(l.date) + '</span></div></div></article>';
  }

  function initMap() {
    if (map || !window.L || !mapEl) return;
    map = L.map(mapEl).setView([36.7538, 3.0588], 11); // Alger
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19, attribution: '&copy; OpenStreetMap'
    }).addTo(map);
  }

  function renderMarkers(items) {
    if (!map) return;
    Object.keys(markers).forEach(function (id) { map.removeLayer(markers[id]); });
    markers = {};
    items.forEach(function (l) {
      if (!l.lat || !l.lng) return;
      var m = L.marker([l.lat, l.lng]).addTo(map).bindPopup('<strong>' + esc(l.prix_label) + '</strong><br>' + esc(l.title));
      m.on('click', function () { highlight(l.id, true); });
      m.on('mouseover', function () { highlight(l.id, false); });
      markers[l.id] = m;
    });
  }

  function highlight(id, scroll) {
    section.querySelectorAll('.nova-card.is-active').forEach(function (c) { c.classList.remove('is-active'); });
    var card = grid.querySelector('.nova-card[data-id="' + id + '"]');
    if (card) {
      card.classList.add('is-active');
      if (scroll) card.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    if (markers[id]) markers[id].openPopup();
  }

  // Survol / clic carte → liste, et survol de la carte → mise en avant du marqueur
  grid.addEventListener('mouseover', function (e) {
    var c = e.target.closest('.nova-card');
    if (c && markers[c.dataset.id]) highlight(c.dataset.id, false);
  });

  grid.addEventListener('click', function (e) {
    var fav = e.target.closest('[data-fav]');
    if (fav) {
      var pressed = fav.getAttribute('aria-pressed') !== 'true';
      fav.setAttribute('aria-pressed', String(pressed));
      return;
    }
    var c = e.target.closest('.nova-card');
    if (c && map && markers[c.dataset.id]) { map.setView(markers[c.dataset.id].getLatLng(), 14); highlight(c.dataset.id, false); }
  });

  function load() {
    var params = new URLSearchParams();
    if (form) {
      new FormData(form).forEach(function (v, k) { if (v !== '') params.append(k, v); });
    }
    grid.setAttribute('aria-busy', 'true');
    grid.innerHTML = '<p class="nova-status">' + esc(novaImmo.i18n.loading) + '</p>';
    fetch(novaImmo.restUrl + '?' + params.toString(), { headers: { 'Accept': 'application/json' } })
      .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
      .then(function (items) {
        grid.innerHTML = items.length ? items.map(cardHtml).join('') : '<p class="nova-status">' + esc(novaImmo.i18n.noResults) + '</p>';
        renderMarkers(items);
      })
      .catch(function () { grid.innerHTML = '<p class="nova-status">⚠️</p>'; })
      .finally(function () { grid.removeAttribute('aria-busy'); });
  }

  if (form) {
    form.addEventListener('change', load);
    form.addEventListener('submit', function (e) { e.preventDefault(); load(); });
    var wilaya = form.querySelector('[name="wilaya"]');
    if (wilaya) wilaya.addEventListener('input', function () { clearTimeout(wilaya._t); wilaya._t = setTimeout(load, 400); });
  }

  if (mapToggle) {
    mapToggle.addEventListener('click', function () {
      var on = mapToggle.getAttribute('aria-pressed') !== 'true';
      mapToggle.setAttribute('aria-pressed', String(on));
      layout.classList.toggle('is-map', on);
      if (on) { initMap(); setTimeout(function () { map.invalidateSize(); load(); }, 50); }
    });
  }
})();
