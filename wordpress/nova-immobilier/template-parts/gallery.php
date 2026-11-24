<?php
/** Property gallery: main image, thumbnails and lightbox (behaviour in theme.js). $args['p']. */
defined('ABSPATH') || exit;
$p = $args['p'];
$images = $p['images'];
$count = count($images);
?>
<div class="gallery" data-gallery>
    <div class="gallery__main">
        <button type="button" class="gallery__open" data-gallery-open aria-label="Agrandir la photo">
            <?php foreach ($images as $i => $img) : ?>
                <div class="gallery__slide" data-slide="<?php echo (int) $i; ?>" <?php echo $i ? 'hidden' : ''; ?>>
                    <?php echo nova_image($img, 'nova-large', 'ratio-3-2', $i === 0); ?>
                </div>
            <?php endforeach; ?>
        </button>
        <?php if ($count > 1) : ?>
            <button type="button" class="gallery__nav gallery__nav--prev" data-gallery-prev aria-label="Photo précédente"><?php echo nova_icon('chevronLeft', 22); ?></button>
            <button type="button" class="gallery__nav gallery__nav--next" data-gallery-next aria-label="Photo suivante"><?php echo nova_icon('chevronRight', 22); ?></button>
        <?php endif; ?>
        <span class="gallery__counter" aria-live="polite"><span data-gallery-index>1</span> / <?php echo (int) $count; ?></span>
        <button type="button" class="gallery__expand" data-gallery-open aria-label="Afficher en plein écran"><?php echo nova_icon('expand', 18); ?></button>
    </div>
    <?php if ($count > 1) : ?>
        <div class="gallery__thumbs" role="tablist" aria-label="Photos — <?php echo esc_attr($p['title']); ?>">
            <?php foreach ($images as $i => $img) : ?>
                <button type="button" role="tab" class="gallery__thumb<?php echo $i ? '' : ' is-active'; ?>" aria-selected="<?php echo $i ? 'false' : 'true'; ?>" aria-label="Photo <?php echo $i + 1; ?> : <?php echo esc_attr($img['alt']); ?>" data-gallery-thumb="<?php echo (int) $i; ?>">
                    <?php echo nova_image($img, 'nova-thumb', 'ratio-4-3', false, ''); ?>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div class="lightbox" role="dialog" aria-modal="true" aria-label="Galerie photos — <?php echo esc_attr($p['title']); ?>" hidden data-lightbox>
        <button type="button" class="lightbox__close" data-lightbox-close aria-label="Fermer la galerie"><?php echo nova_icon('close', 24); ?></button>
        <figure class="lightbox__figure">
            <div class="img-frame is-loaded lightbox__img"><img src="<?php echo esc_attr(NOVA_BLANK); ?>" alt="" data-lightbox-img></div>
            <figcaption><span data-lightbox-caption></span> <span>· <span data-gallery-index>1</span> / <?php echo (int) $count; ?></span></figcaption>
        </figure>
        <?php if ($count > 1) : ?>
            <button type="button" class="lightbox__nav lightbox__nav--prev" data-gallery-prev aria-label="Photo précédente"><?php echo nova_icon('chevronLeft', 28); ?></button>
            <button type="button" class="lightbox__nav lightbox__nav--next" data-gallery-next aria-label="Photo suivante"><?php echo nova_icon('chevronRight', 28); ?></button>
        <?php endif; ?>
    </div>
</div>
