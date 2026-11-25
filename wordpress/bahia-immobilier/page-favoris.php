<?php
/** Favorites: IDs come from the `bahia_favs` cookie written by theme.js (mirrors localStorage). */
defined('ABSPATH') || exit;
nocache_headers();
get_header();
$ids = array_filter(array_map('absint', explode(',', sanitize_text_field(wp_unslash($_COOKIE['bahia_favs'] ?? '')))));
$items = $ids ? array_map('bahia_get_property', get_posts(['post_type' => 'property', 'post__in' => $ids, 'orderby' => 'post__in', 'numberposts' => 50])) : [];
get_template_part('template-parts/page-header', null, ['title' => bahia_t('Mes favoris'), 'subtitle' => bahia_t('Les biens que vous avez sauvegardés sont conservés sur cet appareil.'), 'crumbs' => [[bahia_t('Mes favoris'), null]]]);
?>
<section class="section">
    <div class="container">
        <?php if ($items) : ?>
            <div class="property-grid" data-favorites-grid>
                <?php foreach ($items as $p) get_template_part('template-parts/property-card', null, ['p' => $p]); ?>
            </div>
        <?php else : ?>
            <div class="empty-state">
                <span class="empty-state__icon"><?php echo bahia_icon('heart', 26); ?></span>
                <h2><?php echo esc_html(bahia_t('Aucun favori pour le moment')); ?></h2>
                <p><?php echo esc_html(bahia_t('Cliquez sur le cœur d’une annonce pour la retrouver facilement ici et la comparer avec d’autres biens.')); ?></p>
                <div class="empty-state__actions"><a href="<?php echo esc_url(get_post_type_archive_link('property')); ?>" class="btn btn--primary btn--sm"><?php echo esc_html(bahia_t('Parcourir nos biens')); ?></a></div>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php get_footer();
