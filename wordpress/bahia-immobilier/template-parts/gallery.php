<?php
/** Property gallery: main photo, thumbnail strip and full-screen view (behaviour in theme.js). $args['p']. */
defined('ABSPATH') || exit;
$p = $args['p'];
$images = $p['images'];
$count = count($images);
$of = bahia_is_ar() ? ' من ' : ' / ';
?>
<div class="gallery rv-gallery" data-gallery>
    <div class="gallery__main rv-gallery__main">
        <button type="button" class="rv-gallery__open" data-gallery-open aria-label="<?php echo esc_attr(bahia_t('Agrandir la photo')); ?>">
            <?php foreach ($images as $i => $img) : ?>
                <div class="rv-gallery__slide" data-slide="<?php echo (int) $i; ?>" <?php echo $i ? 'hidden' : ''; ?>>
                    <?php echo bahia_image($img, 'bahia-large', 'rv-gallery__img', $i === 0); ?>
                </div>
            <?php endforeach; ?>
        </button>
        <span class="rv-gallery__counter" aria-live="polite"><span data-gallery-index>1</span><?php echo esc_html($of . $count); ?></span>
        <?php echo bahia_stripes('rv-stripes--corner rv-stripes--top-end'); ?>
        <?php if ($count > 1) : ?>
            <button type="button" class="rv-gallery__nav rv-gallery__nav--prev" data-gallery-prev aria-label="<?php echo esc_attr(bahia_t('Photo précédente')); ?>"><?php echo bahia_icon('chevronLeft', 22, 'rv-flip'); ?></button>
            <button type="button" class="rv-gallery__nav rv-gallery__nav--next" data-gallery-next aria-label="<?php echo esc_attr(bahia_t('Photo suivante')); ?>"><?php echo bahia_icon('chevronRight', 22, 'rv-flip'); ?></button>
        <?php endif; ?>
    </div>
    <?php if ($count > 1) : ?>
        <div class="rv-gallery__thumbs" role="tablist" aria-label="<?php echo esc_attr(bahia_t('Photos du bien')); ?>">
            <?php foreach ($images as $i => $img) : ?>
                <button type="button" role="tab" class="gallery__thumb rv-gallery__thumb<?php echo $i ? '' : ' is-active'; ?>" aria-selected="<?php echo $i ? 'false' : 'true'; ?>" aria-label="<?php echo esc_attr(bahia_t('Photo') . ' ' . ($i + 1) . ' : ' . $img['alt']); ?>" data-gallery-thumb="<?php echo (int) $i; ?>">
                    <?php echo bahia_image($img, 'bahia-thumb', '', false, ''); ?>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div class="rv-lightbox" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr(bahia_t('Photos du bien')); ?>" hidden data-lightbox>
        <button type="button" class="rv-lightbox__close" data-lightbox-close aria-label="<?php echo esc_attr(bahia_t('Fermer')); ?>"><?php echo bahia_icon('close', 24); ?></button>
        <figure class="rv-lightbox__figure">
            <img src="<?php echo esc_attr(BAHIA_BLANK); ?>" alt="" data-lightbox-img>
            <figcaption><span data-lightbox-caption></span> · <span data-gallery-index>1</span><?php echo esc_html($of . $count); ?></figcaption>
        </figure>
        <?php if ($count > 1) : ?>
            <button type="button" class="rv-lightbox__nav rv-lightbox__nav--prev" data-gallery-prev aria-label="<?php echo esc_attr(bahia_t('Photo précédente')); ?>"><?php echo bahia_icon('chevronLeft', 28, 'rv-flip'); ?></button>
            <button type="button" class="rv-lightbox__nav rv-lightbox__nav--next" data-gallery-next aria-label="<?php echo esc_attr(bahia_t('Photo suivante')); ?>"><?php echo bahia_icon('chevronRight', 28, 'rv-flip'); ?></button>
        <?php endif; ?>
    </div>
</div>
