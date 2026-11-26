/* Contact: channel tiles, validated form (subject and reference prefilled from the URL), office details. */
window.TI = window.TI || {};
TI.pages = TI.pages || {};
TI.pages.contact = function () {
  var form = TI.$('#contact-form');
  var wrap = TI.$('#contact-form-wrap');
  var success = TI.$('#contact-success');
  var sent = null;
  var p = TI.params();
  var subj = { vendre: 'sell', estimer: 'estimate', acheter: 'buy', louer: 'rent' }[p.get('sujet')];
  if (subj) form.elements.subject.value = subj;
  if (p.get('ref') && TI.findProperty(p.get('ref'))) form.elements.ref.value = p.get('ref');
  var img = TI.$('#office-img'); if (img) img.src = TI.media.exterior('local', 21, { time: 'dusk' });

  function render() {
    document.title = TI.t('meta_contact');
    var c = TI.config;
    TI.$('#ch-wa').href = TI.wa(TI.t('wa_general'));
    TI.$('#ch-phone-num').textContent = c.phone;
    TI.$('#ch-call').href = 'tel:' + c.phoneHref;
    TI.$('#ch-mail').href = 'mailto:' + c.email;
    TI.$('#ch-mail-addr').textContent = c.email;
    TI.$('#office-address').textContent = TI.L(c.address);
    TI.$('#office-hours').innerHTML = TI.esc(TI.L(c.hours)) + '<br>' + TI.esc(TI.L(c.saturday)) + '<br>' + TI.esc(TI.L(c.friday));
    TI.$('#office-map').href = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(c.mapQuery);
    if (sent) showSuccess(sent);
  }
  function showSuccess(d) {
    sent = d;
    TI.$('#success-text').textContent = TI.t('form_success', { name: d.name, phone: d.phone });
    TI.$('#success-wa').href = TI.wa(TI.t('wa_general'));
    wrap.hidden = true; success.hidden = false;
  }
  TI.forms.bind(form, function (d) { showSuccess(d); TI.$('#success-title').focus(); });
  TI.$('#success-again').addEventListener('click', function () {
    sent = null; form.reset();
    TI.forms.fields(form).forEach(function (el) { TI.forms.show(el, ''); });
    success.hidden = true; wrap.hidden = false;
    form.elements.name.focus();
  });
  render();
  return { render: render };
};
