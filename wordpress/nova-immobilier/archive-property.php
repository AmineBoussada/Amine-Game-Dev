<?php
defined('ABSPATH') || exit;
get_header();
global $wp_query;
$f = nova_current_filters();
$total = (int) $wp_query->found_posts;
$heading = nova_t($f['transaction'] === 'vente' ? 'Biens à vendre' : ($f['transaction'] === 'location' ? 'Biens à louer' : 'Nos biens immobiliers'));

$chips = [];
if ($f['transaction']) $chips[] = [nova_t(nova_transaction_labels()[$f['transaction']]), ['transaction' => '', 'prix_min' => '', 'prix_max' => '']];
if ($f['bien']) $chips[] = [nova_t(nova_type_labels()[$f['bien']]), ['bien' => '']];
if ($f['wilaya']) $chips[] = [nova_t($f['wilaya']), ['wilaya' => '']];
if ($f['commune']) $chips[] = [nova_t($f['commune']), ['commune' => '']];
if ($f['prix_min']) $chips[] = [sprintf(nova_t('Dès %s'), nova_price_short($f['prix_min'])), ['prix_min' => '']];
if ($f['prix_max']) $chips[] = [sprintf(nova_t('Jusqu’à %s'), nova_price_short($f['prix_max'])), ['prix_max' => '']];
if ($f['surface']) $chips[] = [$f['surface'] . ' ' . nova_t('m²') . ' +', ['surface' => '']];
if ($f['pieces']) $chips[] = [sprintf(nova_t('%s+ pièces'), $f['pieces']), ['pieces' => '']];

get_template_part('template-parts/page-header', null, [
    'title' => $heading,
    'subtitle' => nova_t('Trouvez le bien qui correspond à votre projet.'),
    'crumbs' => [[nova_t('Nos biens'), null]],
]);
?>
<section class="section listings">
    <div class="container listings__layout">
        <aside class="listings__sidebar" aria-label="<?php echo esc_attr(nova_t('Filtres de recherche')); ?>" data-filter-drawer>
            <div class="listings__sidebar-head">
                <h2><?php echo esc_html(nova_t('Filtrer')); ?></h2>
                <button type="button" class="icon-btn listings__close" data-filter-close aria-label="<?php echo esc_attr(nova_t('Fermer les filtres')); ?>"><?php echo nova_icon('close'); ?></button>
            </div>
            <?php get_template_part('template-parts/filter-panel'); ?>
            <div class="listings__drawer-footer">
                <button type="button" class="btn btn--primary btn--block" data-filter-close><?php echo esc_html(sprintf(nova_t('Voir %s'), $total . ' ' . nova_t($total > 1 ? 'résultats' : 'résultat'))); ?></button>
            </div>
        </aside>
        <div class="listings__backdrop" data-filter-close hidden></div>

        <div class="listings__main">
            <div class="listings__toolbar">
                <p class="listings__count"><strong><?php echo $total; ?></strong> <?php echo esc_html(nova_t($total > 1 ? 'biens trouvés' : 'bien trouvé')); ?></p>
                <div class="listings__tools">
                    <button type="button" class="btn btn--outline btn--sm listings__filter-btn" data-filter-open><?php echo nova_icon('filter', 18); ?> <?php echo esc_html(nova_t('Filtres')); ?> <?php if ($chips) : ?><span class="pill-count"><?php echo count($chips); ?></span><?php endif; ?></button>
                    <label class="sort-select"><span class="sr-only"><?php echo esc_html(nova_t('Trier par')); ?></span>
                        <select name="tri" form="nova-filters" data-autosubmit>
                            <?php foreach (nova_sort_options() as $k => $v) : ?><option value="<?php echo esc_attr($k); ?>" <?php selected($f['tri'], $k); ?>><?php echo esc_html(nova_t($v)); ?></option><?php endforeach; ?>
                        </select>
                    </label>
                </div>
            </div>

            <?php if ($chips) : ?>
                <ul class="active-filters" aria-label="<?php echo esc_attr(nova_t('Filtres actifs')); ?>">
                    <?php foreach ($chips as [$label, $clear]) : ?>
                        <li><a class="chip chip--removable" href="<?php echo esc_url(nova_filters_url($clear)); ?>"><?php echo esc_html($label); ?> <?php echo nova_icon('close', 14); ?><span class="sr-only"><?php echo esc_html(nova_t('Retirer ce filtre')); ?></span></a></li>
                    <?php endforeach; ?>
                    <li><a class="active-filters__clear" href="<?php echo esc_url(get_post_type_archive_link('property')); ?>"><?php echo esc_html(nova_t('Tout effacer')); ?></a></li>
                </ul>
            <?php endif; ?>

            <?php if (have_posts()) : ?>
                <div class="property-grid">
                    <?php $i = 0; while (have_posts()) : the_post();
                        get_template_part('template-parts/property-card', null, ['p' => nova_get_property(), 'class' => 'reveal reveal-delay-' . ($i++ % 3)]);
                    endwhile; ?>
                </div>
                <?php
                $links = paginate_links(['type' => 'array', 'prev_text' => nova_t('‹ Précédent'), 'next_text' => nova_t('Suivant ›')]);
                if ($links) : ?>
                    <nav class="pagination" aria-label="<?php echo esc_attr(nova_t('Pagination')); ?>"><?php echo implode('', $links); ?></nav>
                <?php endif; ?>
            <?php else : ?>
                <div class="empty-state">
                    <span class="empty-state__icon"><?php echo nova_icon('search', 26); ?></span>
                    <h2><?php echo esc_html(nova_t('Aucun bien ne correspond à vos critères')); ?></h2>
                    <p><?php echo esc_html(nova_t('Élargissez votre recherche ou confiez-nous votre projet : nous vous informerons dès qu’un bien correspondant arrive en exclusivité.')); ?></p>
                    <div class="empty-state__actions">
                        <a href="<?php echo esc_url(get_post_type_archive_link('property')); ?>" class="btn btn--outline btn--sm"><?php echo esc_html(nova_t('Réinitialiser les filtres')); ?></a>
                        <a href="<?php echo esc_url(nova_contact_url('Recherche personnalisée', 'formulaire')); ?>" class="btn btn--primary btn--sm"><?php echo esc_html(nova_t('Confier ma recherche')); ?></a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php get_footer();
