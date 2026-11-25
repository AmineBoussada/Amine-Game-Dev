/**
 * Riviera layout: light/dark switch and visit planner.
 * Favorites, filters, gallery, menu and form validation come from theme.js (shared with the base theme).
 */
(function () {
  'use strict';
  var root = document.documentElement;

  /* ---------- Light / dark (remembered on this device) ---------- */
  function setTheme(dark) {
    if (dark) root.setAttribute('data-theme', 'sombre');
    else root.removeAttribute('data-theme');
    var value = dark ? 'sombre' : 'clair';
    try { localStorage.setItem('bahia_theme', value); } catch (e) { /* storage unavailable */ }
    document.cookie = 'bahia_theme=' + value + '; path=/; max-age=31536000; SameSite=Lax';
    paintThemeButtons();
  }
  function paintThemeButtons() {
    var dark = root.getAttribute('data-theme') === 'sombre';
    document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
      btn.setAttribute('aria-pressed', String(dark));
    });
  }
  document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () { setTheme(root.getAttribute('data-theme') !== 'sombre'); });
  });
  paintThemeButtons();

  /* ---------- Header height (sticky filter bar, mobile menu) ---------- */
  var header = document.querySelector('[data-header]');
  var setHeaderHeight = function () {
    if (header) root.style.setProperty('--header-h', Math.round(header.offsetHeight + (parseFloat(getComputedStyle(header).top) || 0)) + 'px');
  };
  setHeaderHeight();
  window.addEventListener('resize', setHeaderHeight);

  /* ---------- Visit planner (property page) ---------- */
  var box = document.querySelector('[data-visit]');
  if (!box) return;
  var i18n = JSON.parse(box.querySelector('[data-visit-i18n]').textContent);
  var daysEl = box.querySelector('[data-visit-days]');
  var slotsEl = box.querySelector('[data-visit-slots]');
  var summary = box.querySelector('[data-visit-summary]');
  var ref = box.getAttribute('data-visit-ref');
  var fmt = function (s) { var a = arguments; var i = 0; return s.replace(/%(\d)\$s|%s/g, function (m, n) { return a[n ? Number(n) : ++i]; }); };

  // Next five opening days: Friday closed, Saturday morning only.
  var days = [];
  var d = new Date();
  d.setHours(12, 0, 0, 0);
  while (days.length < 5) {
    d.setDate(d.getDate() + 1);
    if (d.getDay() === 5) continue;
    days.push(new Date(d));
  }
  var slotsFor = function (day) { return day.getDay() === 6 ? ['09:30', '11:00'] : ['09:30', '11:00', '14:00', '15:30', '17:00']; };
  var state = { day: 0, slot: '' };
  var longDate = function (day) { return i18n.days[day.getDay()] + ' ' + day.getDate() + ' ' + i18n.months[day.getMonth()]; };

  function radio(label, on, onPick, extraClass) {
    var b = document.createElement('button');
    b.type = 'button';
    b.setAttribute('role', 'radio');
    b.setAttribute('aria-checked', String(on));
    b.className = 'rv-visit__opt' + (extraClass ? ' ' + extraClass : '') + (on ? ' is-active' : '');
    b.innerHTML = label;
    b.addEventListener('click', onPick);
    return b;
  }
  function render() {
    daysEl.textContent = '';
    days.forEach(function (day, i) {
      daysEl.appendChild(radio('<span>' + i18n.dows[day.getDay()] + '</span><strong>' + day.getDate() + '</strong>', i === state.day, function () {
        state.day = i; state.slot = ''; render();
      }, 'rv-visit__day'));
    });
    slotsEl.textContent = '';
    slotsFor(days[state.day]).forEach(function (s) {
      var b = radio(s, s === state.slot, function () { state.slot = s; render(); });
      b.dir = 'ltr';
      slotsEl.appendChild(b);
    });
    summary.textContent = state.slot ? fmt(i18n.summary, longDate(days[state.day]), state.slot) : fmt(i18n.pick, longDate(days[state.day]));
  }
  render();

  // "Demander cette visite": theme.js selects the subject and scrolls to the form; we fill the message.
  document.querySelectorAll('[data-request-visit]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!state.slot) return;
      var msg = document.querySelector('#formulaire [data-message]');
      if (msg) msg.value = fmt(i18n.message, ref, longDate(days[state.day]), state.slot);
    });
  });
})();
