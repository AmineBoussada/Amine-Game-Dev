<?php
defined('ABSPATH') || exit;
get_header();
global $wp_query;
$f = bahia_current_filters();
$total = (int) $wp_query->found_posts;
$archive = get_post_type_archive_link('property');
$title = $f['transaction'] === 'vente' ? bahia_t('Biens à vendre') : ($f['transaction'] === 'location' ? bahia_t('Biens à louer') : sprintf(bahia_t('Biens à %s'), bahia_t('Oran')));
$filtered = (bool) array_filter([$f['transaction'], $f['bien'], $f['wilaya'], $f['commune'], $f['prix_min'], $f['prix_max'], $f['surface'], $f['pieces']]);

get_template_part('template-parts/page-header', null, [
    'title' => $title,
    'subtitle' => bahia_t('Appartements, villas, terrains et locaux. Chaque annonce a été visitée par un conseiller avant sa mise en ligne.'),
    'crumbs' => [[bahia_t('Nos biens'), null]],
]);
get_template_part('template-parts/filter-panel');
?>
<section class="rv-wrap rv-results" aria-label="<?php echo esc_attr(bahia_t('Résultats')); ?>">
    <div class="rv-results__head">
        <p class="rv-count" role="status"><strong><?php echo esc_html(bahia_num($total)); ?></strong> <?php echo esc_html(bahia_t($total > 1 ? 'biens trouvés' : 'bien trouvé')); ?></p>
        <?php if ($filtered) : ?><a class="rv-link" href="<?php echo esc_url($archive); ?>"><?php echo esc_html(bahia_t('Effacer les filtres')); ?></a><?php endif; ?>
    </div>

    <?php if (have_posts()) : ?>
        <div class="property-grid rv-grid">
            <?php while (have_posts()) : the_post();
                get_template_part('template-parts/property-card', null, ['p' => bahia_get_property(), 'heading' => 'h2']);
            endwhile; ?>
        </div>
        <?php
        $links = paginate_links(['type' => 'array', 'prev_text' => bahia_t('Précédent'), 'next_text' => bahia_t('Suivant')]);
        if ($links) : ?>
            <nav class="rv-pagination" aria-label="<?php echo esc_attr(bahia_t('Pagination')); ?>"><?php echo implode('', $links); ?></nav>
        <?php endif; ?>
    <?php else : ?>
        <div class="rv-empty">
            <?php echo bahia_stripes('rv-stripes--square'); ?>
            <h2><?php echo esc_html(bahia_t('Aucun bien pour ces critères')); ?></h2>
            <p><?php echo esc_html(bahia_t('Élargissez la recherche ou confiez-nous votre projet : nous vous appelons dès qu’un bien correspond.')); ?></p>
            <div class="rv-actions rv-actions--center">
                <a href="<?php echo esc_url($archive); ?>" class="rv-btn rv-btn--ghost"><?php echo esc_html(bahia_t('Effacer les filtres')); ?></a>
                <a href="<?php echo esc_url(bahia_contact_url('recherche', 'formulaire')); ?>" class="rv-btn"><?php echo esc_html(bahia_t('Confier ma recherche')); ?></a>
            </div>
        </div>
    <?php endif; ?>
</section>
<?php get_footer();
