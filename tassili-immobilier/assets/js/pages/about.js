/* About: team cards and legal details from TI.config / TI.agents. */
window.TI = window.TI || {};
TI.pages = TI.pages || {};
TI.pages.about = function () {
  var img = TI.$('#about-img'); if (img) img.src = TI.media.exterior('local', 21, { time: 'day' });
  function render() {
    document.title = TI.t('meta_about');
    TI.$('#team-grid').innerHTML = TI.agents.map(function (a) {
      return '<article class="member"><span class="avatar">' + TI.initials(a.name) + '</span><div><b>' + TI.esc(a.name) + '</b><p class="role">' + TI.esc(TI.L(a.role)) + '</p></div>' +
        '<p class="langs">' + TI.esc(TI.t('langs_spoken', { l: TI.L(a.langs) })) + '</p>' +
        '<a class="btn btn-wa btn-sm" href="' + TI.wa(TI.t('wa_general'), a.whatsapp) + '" target="_blank" rel="noopener">' + TI.icon('wa') + TI.t('member_wa') + '</a></article>';
    }).join('');
    TI.$('#legal-office').textContent = TI.L(TI.config.address);
    TI.$('#legal-rc').textContent = TI.config.rc;
    TI.$('#legal-nif').textContent = TI.config.nif;
  }
  render();
  return { render: render };
};
