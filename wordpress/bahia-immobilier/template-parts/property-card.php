<?php
/** Property card. Expects $args['p'] (from bahia_get_property()) and optional $args['class']. */
defined('ABSPATH') || exit;
$p = $args['p'];
$cover = $p['images'][0];
$unavailable = in_array($p['status'], ['vendu', 'loue'], true);
?>
<article class="property-card <?php echo esc_attr($args['class'] ?? ''); ?><?php echo $unavailable ? ' is-unavailable' : ''; ?>">
    <div class="property-card__media">
        <a href="<?php echo esc_url($p['url']); ?>" tabindex="-1" aria-hidden="true"><?php echo bahia_image($cover, 'bahia-card'); ?></a>
        <div class="property-card__badges">
            <span class="badge badge--<?php echo esc_attr($p['transaction']); ?>"><?php echo esc_html(bahia_t(bahia_transaction_labels()[$p['transaction']])); ?></span>
            <?php if ($p['status'] !== 'disponible') : ?>
                <span class="badge badge--status badge--<?php echo esc_attr($p['status']); ?>"><?php echo esc_html(bahia_t(bahia_status_labels()[$p['status']])); ?></span>
            <?php endif; ?>
        </div>
        <button type="button" class="fav-btn property-card__fav" data-fav="<?php echo esc_attr($p['id']); ?>" aria-pressed="false" aria-label="<?php echo esc_attr(sprintf(bahia_t('Ajouter « %s » aux favoris'), $p['title'])); ?>" title="<?php echo esc_attr(bahia_t('Ajouter aux favoris')); ?>"><?php echo bahia_icon('heart', 18); ?></button>
        <span class="property-card__count" aria-hidden="true"><?php echo bahia_icon('image', 14); ?> <?php echo count($p['images']); ?></span>
    </div>
    <div class="property-card__body">
        <div class="property-card__meta">
            <span><?php echo esc_html(bahia_kind($p)); ?></span>
            <span class="dot" aria-hidden="true"></span>
            <span class="property-card__ref"><?php echo esc_html(bahia_t('Réf.')); ?> <bdi dir="ltr"><?php echo esc_html($p['reference']); ?></bdi></span>
        </div>
        <h3 class="property-card__title"<?php echo bahia_lang_attr($p['title_lang']); ?>><a href="<?php echo esc_url($p['url']); ?>" class="stretched-link"><?php echo esc_html($p['title']); ?></a></h3>
        <p class="property-card__location"><?php echo bahia_icon('pin', 16); ?> <?php echo esc_html(bahia_place($p)); ?></p>
        <ul class="property-card__specs" aria-label="<?php echo esc_attr(bahia_t('Caractéristiques')); ?>">
            <?php if ($p['surface']) : ?><li><?php echo bahia_icon('area', 16); ?><span><?php echo esc_html(bahia_num($p['surface']) . ' ' . bahia_t('m²')); ?></span></li><?php endif; ?>
            <?php if ($p['rooms']) : ?><li><?php echo bahia_icon('rooms', 16); ?><span><?php echo esc_html($p['rooms'] . ' ' . bahia_t($p['rooms'] > 1 ? 'pièces' : 'pièce')); ?></span></li><?php endif; ?>
            <?php if ($p['bedrooms']) : ?><li><?php echo bahia_icon('bed', 16); ?><span><?php echo esc_html($p['bedrooms'] . ' ' . bahia_t('ch.')); ?></span></li><?php endif; ?>
            <?php if ($p['type'] === 'terrain') : ?><li><?php echo bahia_icon('layers', 16); ?><span><?php echo esc_html(bahia_t('Constructible')); ?></span></li><?php endif; ?>
        </ul>
        <div class="property-card__footer">
            <p class="property-card__price"><?php echo esc_html(bahia_price($p['price'])); ?><?php if ($p['transaction'] === 'location') : ?><small><?php echo esc_html(bahia_suffix($p)); ?></small><?php endif; ?></p>
            <span class="property-card__more" aria-hidden="true"><?php echo esc_html(bahia_t('Voir le bien')); ?> <?php echo bahia_icon('arrow', 16, 'rtl-flip'); ?></span>
        </div>
    </div>
</article>
