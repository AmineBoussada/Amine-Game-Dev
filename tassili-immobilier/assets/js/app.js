/* Entry point: renders the shared chrome, translates static text, starts the page controller
   named by <body data-page>, and re-renders everything when the language changes. */
window.TI = window.TI || {};
(function (TI) {
  function start() {
    TI.renderChrome();
    TI.applyI18n();
    TI.bindGlobal();
    var page = document.body.getAttribute('data-page');
    var ctl = TI.pages[page] ? TI.pages[page]() : null;
    document.addEventListener('ti:lang', function () {
      TI.renderChrome();
      TI.applyI18n();
      if (ctl && ctl.render) ctl.render();
      TI.applyI18n();
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
  else start();
})(window.TI);
