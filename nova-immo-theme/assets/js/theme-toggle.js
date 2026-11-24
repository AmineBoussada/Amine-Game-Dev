/* Bascule clair / sombre — préférence mémorisée (localStorage, facultatif). */
(function () {
  var root = document.documentElement;
  try {
    var saved = localStorage.getItem('nova-theme');
    if (saved) root.setAttribute('data-theme', saved);
  } catch (e) { /* stockage indisponible : on garde le thème système */ }

  document.addEventListener('click', function (event) {
    var btn = event.target.closest('[data-theme-toggle]');
    if (!btn) return;
    var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    try { localStorage.setItem('nova-theme', next); } catch (e) {}
  });
})();
