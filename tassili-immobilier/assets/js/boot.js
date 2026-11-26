/* Runs in <head> before first paint: sets language, direction and theme so the page never flashes. */
(function () {
  var d = document.documentElement, lang = 'fr', theme = null;
  try {
    var p = new URLSearchParams(location.search).get('lang');
    if (p === 'ar' || p === 'fr') { lang = p; localStorage.setItem('ti-lang', p); }
    else { lang = localStorage.getItem('ti-lang') === 'ar' ? 'ar' : 'fr'; }
    theme = localStorage.getItem('ti-theme');
  } catch (e) {
    var q = /[?&]lang=(ar|fr)/.exec(location.search); if (q) lang = q[1];
  }
  d.lang = lang;
  d.dir = lang === 'ar' ? 'rtl' : 'ltr';
  if (theme === 'dark' || theme === 'light') d.setAttribute('data-theme', theme);
})();
