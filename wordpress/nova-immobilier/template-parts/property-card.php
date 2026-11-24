<?php
/** Property card. Expects $args['p'] (from nova_get_property()) and optional $args['class']. */
defined('ABSPATH') || exit;
$p = $args['p'];
$cover = $p['images'][0];
$unavailable = in_array($p['status'], ['vendu', 'loue'], true);
?>
<article class="property-card <?php echo esc_attr($args['class'] ?? ''); ?><?php echo $unavailable ? ' is-unavailable' : ''; ?>">
    <div class="property-card__media">
        <a href="<?php echo esc_url($p['url']); ?>" tabindex="-1" aria-hidden="true"><?php echo nova_image($cover, 'nova-card'); ?></a>
        <div class="property-card__badges">
            <span class="badge badge--<?php echo esc_attr($p['transaction']); ?>"><?php echo esc_html(nova_transaction_labels()[$p['transaction']]); ?></span>
            <?php if ($p['status'] !== 'disponible') : ?>
                <span class="badge badge--status badge--<?php echo esc_attr($p['status']); ?>"><?php echo esc_html(nova_status_labels()[$p['status']]); ?></span>
            <?php endif; ?>
        </div>
        <button type="button" class="fav-btn property-card__fav" data-fav="<?php echo esc_attr($p['id']); ?>" aria-pressed="false" aria-label="Ajouter « <?php echo esc_attr($p['title']); ?> » aux favoris" title="Ajouter aux favoris"><?php echo nova_icon('heart', 18); ?></button>
        <span class="property-card__count" aria-hidden="true"><?php echo nova_icon('image', 14); ?> <?php echo count($p['images']); ?></span>
    </div>
    <div class="property-card__body">
        <div class="property-card__meta">
            <span><?php echo esc_html(nova_type_label($p)); ?></span>
            <span class="dot" aria-hidden="true"></span>
            <span class="property-card__ref">Réf. <?php echo esc_html($p['reference']); ?></span>
        </div>
        <h3 class="property-card__title"><a href="<?php echo esc_url($p['url']); ?>" class="stretched-link"><?php echo esc_html($p['title']); ?></a></h3>
        <p class="property-card__location"><?php echo nova_icon('pin', 16); ?> <?php echo esc_html(trim($p['commune'] . ', ' . $p['wilaya'], ', ')); ?></p>
        <ul class="property-card__specs" aria-label="Caractéristiques">
            <?php if ($p['surface']) : ?><li><?php echo nova_icon('area', 16); ?><span><?php echo esc_html($p['surface']); ?> m²</span></li><?php endif; ?>
            <?php if ($p['rooms']) : ?><li><?php echo nova_icon('rooms', 16); ?><span><?php echo esc_html(nova_plural($p['rooms'], 'pièce')); ?></span></li><?php endif; ?>
            <?php if ($p['bedrooms']) : ?><li><?php echo nova_icon('bed', 16); ?><span><?php echo (int) $p['bedrooms']; ?> ch.</span></li><?php endif; ?>
            <?php if ($p['type'] === 'terrain') : ?><li><?php echo nova_icon('layers', 16); ?><span>Constructible</span></li><?php endif; ?>
        </ul>
        <div class="property-card__footer">
            <p class="property-card__price"><?php echo esc_html(nova_format_price($p['price'])); ?><?php if (nova_price_suffix($p)) : ?><small><?php echo esc_html(nova_price_suffix($p)); ?></small><?php endif; ?></p>
            <span class="property-card__more" aria-hidden="true">Voir le bien <?php echo nova_icon('arrow', 16); ?></span>
        </div>
    </div>
</article>
