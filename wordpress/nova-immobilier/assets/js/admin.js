/* NOVA IMMOBILIER — admin helpers (photo gallery, price preview, settings images). */
(function ($) {
  'use strict';

  // ---------- Property gallery ----------
  $('[data-nova-gallery]').each(function () {
    var $box = $(this);
    var $input = $box.find('[data-gallery-input]');
    var $list = $box.find('[data-gallery-list]');
    var $empty = $box.find('[data-gallery-empty]');
    var frame;

    function sync() {
      var ids = $list.children().map(function () { return $(this).data('id'); }).get();
      $input.val(ids.join(','));
      $empty.prop('hidden', ids.length > 0);
    }

    $list.sortable({ items: '> li', tolerance: 'pointer', update: sync });

    $box.on('click', '.nova-gallery__remove', function () {
      $(this).closest('li').remove();
      sync();
    });

    $box.on('click', '[data-gallery-add]', function (e) {
      e.preventDefault();
      if (!frame) {
        frame = wp.media({ title: 'Photos du bien', button: { text: 'Ajouter au bien' }, library: { type: 'image' }, multiple: 'add' });
        frame.on('select', function () {
          var existing = $input.val().split(',').filter(Boolean).map(Number);
          frame.state().get('selection').each(function (att) {
            var a = att.toJSON();
            if (existing.indexOf(a.id) !== -1) return;
            var url = (a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail : a).url;
            $list.append(
              $('<li>').attr('data-id', a.id).data('id', a.id)
                .append($('<img>').attr({ src: url, alt: a.alt || '' }))
                .append('<button type="button" class="nova-gallery__remove" aria-label="Retirer cette photo">×</button>')
            );
          });
          sync();
        });
      }
      frame.open();
    });
  });

  // ---------- Price preview + label for rentals ----------
  var $price = $('#nova-field-price');
  var $tx = $('#nova-field-transaction');
  var $preview = $('[data-price-preview]');
  var $label = $('label[for="nova-field-price"]');
  function fmt(n) { return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' '); }
  function updatePrice() {
    var v = parseFloat($price.val());
    var rent = $tx.val() === 'location';
    $label.contents().first()[0].textContent = rent ? 'Loyer mensuel (DA)' : 'Prix (DA)';
    if (!v) { $preview.text(''); return; }
    var txt = fmt(v) + ' DA' + (rent ? ' / mois' : '');
    if (!rent && v >= 1e7) txt += '  ≈ ' + (v / 1e7).toLocaleString('fr-FR', { maximumFractionDigits: 2 }) + ' milliard(s) de centimes';
    $preview.text(txt);
  }
  $price.on('input', updatePrice);
  $tx.on('change', updatePrice);
  if ($price.length) updatePrice();

  // ---------- Settings: image pickers ----------
  $('[data-nova-image]').each(function () {
    var $wrap = $(this);
    var $input = $wrap.find('input[type=hidden]');
    var $preview = $wrap.find('.nova-image-preview');
    var $clear = $wrap.find('[data-nova-image-clear]');
    var frame;
    $wrap.on('click', '[data-nova-image-pick]', function (e) {
      e.preventDefault();
      frame = frame || wp.media({ title: 'Choisir une image', button: { text: 'Utiliser cette image' }, library: { type: 'image' }, multiple: false });
      frame.off('select').on('select', function () {
        var a = frame.state().get('selection').first().toJSON();
        $input.val(a.id);
        $preview.html($('<img>').attr('src', (a.sizes && a.sizes.medium ? a.sizes.medium : a).url));
        $clear.prop('hidden', false);
      });
      frame.open();
    });
    $clear.on('click', function () {
      $input.val('');
      $preview.empty();
      $clear.prop('hidden', true);
    });
  });
})(jQuery);
