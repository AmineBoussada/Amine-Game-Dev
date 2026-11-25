<?php
/** Property card. $args: p (from bahia_get_property()), class, size (small|big|wide), heading (h2|h3). */
defined('ABSPATH') || exit;
$p = $args['p'];
$size = $args['size'] ?? 'small';
$h = $args['heading'] ?? 'h3';
$cover = $p['images'][0];
$rent = $p['transaction'] === 'location';
$status = $p['status'] !== 'disponible' ? bahia_t(bahia_status_labels()[$p['status']] ?? '') : '';
$fav_label = sprintf(bahia_t('Ajouter « %s » aux favoris'), $p['title']);
?>
<article class="property-card rv-card rv-card--<?php echo esc_attr($size); ?> <?php echo esc_attr($args['class'] ?? ''); ?><?php echo in_array($p['status'], ['vendu', 'loue'], true) ? ' is-unavailable' : ''; ?>">
    <div class="rv-card__media">
        <a href="<?php echo esc_url($p['url']); ?>" tabindex="-1" aria-hidden="true"><?php echo bahia_image($cover, $size === 'big' ? 'bahia-large' : 'bahia-card', 'rv-card__img'); ?></a>
        <span class="rv-badge<?php echo $rent ? ' rv-badge--outline' : ''; ?>"><?php echo esc_html(bahia_t(bahia_transaction_labels()[$p['transaction']])); ?><?php echo $status ? ' · ' . esc_html($status) : ''; ?></span>
        <button type="button" class="rv-fav" data-fav="<?php echo esc_attr($p['id']); ?>" aria-pressed="false" aria-label="<?php echo esc_attr($fav_label); ?>" title="<?php echo esc_attr(bahia_t('Ajouter aux favoris')); ?>"><?php echo bahia_icon('heart', 18); ?></button>
        <?php if ($size === 'big') echo bahia_stripes('rv-stripes--corner'); ?>
    </div>
    <div class="rv-card__body">
        <p class="rv-card__meta"><span><?php echo esc_html(bahia_kind($p) . ' · ' . bahia_t($p['commune'])); ?></span><bdi dir="ltr" class="rv-card__ref"><?php echo esc_html($p['reference']); ?></bdi></p>
        <<?php echo $h; ?> class="rv-card__title"<?php echo bahia_lang_attr($p['title_lang']); ?>><a href="<?php echo esc_url($p['url']); ?>" class="stretched-link"><?php echo esc_html($p['title']); ?></a></<?php echo $h; ?>>
        <div class="rv-card__foot">
            <span class="rv-card__specs"><?php echo esc_html(bahia_specs_line($p)); ?></span>
            <p class="property-card__price rv-price"><?php echo esc_html(bahia_price($p['price'])); ?><?php if ($rent) : ?><small><?php echo esc_html(bahia_suffix($p)); ?></small><?php endif; ?></p>
        </div>
    </div>
</article>
