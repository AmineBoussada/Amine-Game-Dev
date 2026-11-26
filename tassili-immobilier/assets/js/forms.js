/* Form validation shared by the contact form and the visit request.
   Fields opt in with data-rule (name | phone | email | message | visitdate | consent) and/or required.
   Each field has an error element with id "<field id>-err". Error text uses data-i18n so it re-translates. */
window.TI = window.TI || {};
(function (TI) {
  var PHONE = /^(?:\+213|00213|0)[567]\d{8}$/;
  var EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
  function localDate(v) { var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(v); return m ? new Date(+m[1], +m[2] - 1, +m[3]) : null; }

  TI.forms = {
    normalizePhone: function (v) { return String(v || '').replace(/[\s.\-()]/g, ''); },
    tomorrow: function () { var d = new Date(); d.setHours(0, 0, 0, 0); d.setDate(d.getDate() + 1); return d; },
    isoDate: function (d) { return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); },
    formatDate: function (v) {
      var d = localDate(v); if (!d) return v;
      try { return d.toLocaleDateString(TI.lang() === 'ar' ? 'ar-DZ' : 'fr-FR', { weekday: 'long', day: 'numeric', month: 'long' }); } catch (e) { return v; }
    },
    check: function (el) {
      var rule = el.getAttribute('data-rule'), v = (el.value || '').trim();
      if (el.type === 'checkbox') return el.required && !el.checked ? (rule === 'consent' ? 'err_consent' : 'err_required') : '';
      if (el.required && !v) return 'err_required';
      if (!v) return '';
      if (rule === 'name' && v.replace(/[^\p{L}]/gu, '').length < 2) return 'err_name';
      if (rule === 'phone' && !PHONE.test(TI.forms.normalizePhone(v))) return 'err_phone';
      if (rule === 'email' && !EMAIL.test(v)) return 'err_email';
      if (rule === 'message' && v.length < 10) return 'err_message';
      if (rule === 'visitdate') {
        var d = localDate(v);
        if (!d || d < TI.forms.tomorrow()) return 'err_date';
        if (d.getDay() === 5) return 'err_friday';
      }
      return '';
    },
    show: function (el, key) {
      var err = document.getElementById(el.id + '-err');
      if (key) {
        el.setAttribute('aria-invalid', 'true');
        if (err) { err.setAttribute('data-i18n', key); err.textContent = TI.t(key); err.hidden = false; el.setAttribute('aria-describedby', err.id); }
      } else {
        el.removeAttribute('aria-invalid');
        if (err) { err.removeAttribute('data-i18n'); err.textContent = ''; err.hidden = true; el.removeAttribute('aria-describedby'); }
      }
    },
    fields: function (form) { return TI.$$('[data-rule], [required]', form); },
    validate: function (form) {
      var first = null;
      TI.forms.fields(form).forEach(function (el) {
        var k = TI.forms.check(el); TI.forms.show(el, k);
        if (k && !first) first = el;
      });
      var sum = TI.$('[data-form-summary]', form);
      if (sum) sum.hidden = !first;
      if (first) first.focus();
      return !first;
    },
    /* onValid(data, form) runs after a short simulated send. */
    bind: function (form, onValid) {
      var tried = false;
      form.setAttribute('novalidate', '');
      var live = function (e) {
        if (!tried || !e.target.matches('[data-rule], [required]')) return;
        TI.forms.show(e.target, TI.forms.check(e.target));
        var sum = TI.$('[data-form-summary]', form);
        if (sum) sum.hidden = !TI.$('[aria-invalid="true"]', form);
      };
      form.addEventListener('input', live);
      form.addEventListener('change', live);
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        tried = true;
        if (!TI.forms.validate(form)) return;
        var btn = TI.$('[type="submit"]', form), label = btn ? btn.innerHTML : '';
        if (btn) { btn.disabled = true; btn.textContent = TI.t('sending'); }
        var data = {};
        TI.$$('input, select, textarea', form).forEach(function (el) {
          if (!el.name) return;
          if (el.type === 'radio') { if (el.checked) data[el.name] = el.value; }
          else if (el.type === 'checkbox') data[el.name] = el.checked;
          else data[el.name] = el.value.trim();
        });
        setTimeout(function () {
          if (btn) { btn.disabled = false; btn.innerHTML = label; }
          tried = false;
          onValid(data, form);
        }, 500);
      });
    }
  };
})(window.TI);
