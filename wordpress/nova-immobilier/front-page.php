<?php
defined('ABSPATH') || exit;
if (nova_render_elementor_page()) {
    return; // page built with Elementor
}
get_header();
$featured = nova_featured_properties(6);
$archive = get_post_type_archive_link('property');
$values = [
    ['selection', 'Sélection de biens', 'Chaque bien est visité, vérifié et documenté avant d’être proposé à nos clients.'],
    ['support', 'Accompagnement personnalisé', 'Un conseiller dédié vous suit de la première visite jusqu’à la signature chez le notaire.'],
    ['map', 'Expertise locale', 'Une connaissance fine des quartiers d’Alger et des prix réels du marché algérien.'],
    ['shield', 'Transparence et confiance', 'Des informations claires, des documents vérifiés et des honoraires annoncés dès le départ.'],
];
$categories = [
    ['appartement', 'Du F2 au F5, en résidence ou en centre-ville', 'building'],
    ['villa', 'Villas familiales et demeures d’exception', 'villa-pool'],
    ['terrain', 'Terrains constructibles avec acte', 'land'],
    ['local', 'Commerces, bureaux et showrooms', 'shop'],
];
$city_parts = preg_split('/[,،]/u', nova_s('city'));
$city = trim((string) end($city_parts));
?>
<section class="hero" aria-labelledby="hero-title">
    <div class="hero__media">
        <?php echo nova_setting_image('hero_image', 'villa-pool', nova_t('Villa contemporaine aux lignes épurées baignée de lumière naturelle'), 'hero__img', true); ?>
        <div class="hero__overlay"></div>
    </div>
    <div class="container hero__content">
        <span class="eyebrow hero__eyebrow"><?php echo esc_html(nova_t('Agence immobilière') . ' · ' . $city); ?></span>
        <h1 id="hero-title" class="hero__title"><?php echo esc_html(nova_s('hero_title')); ?></h1>
        <p class="hero__text"><?php echo esc_html(nova_s('hero_text')); ?></p>
        <div class="hero__actions">
            <a href="<?php echo esc_url($archive); ?>" class="btn btn--bronze"><?php echo esc_html(nova_t('Découvrir nos biens')); ?> <?php echo nova_icon('arrow', 18, 'rtl-flip'); ?></a>
            <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="btn btn--ghost-light"><?php echo esc_html(nova_t('Contactez-nous')); ?></a>
        </div>
    </div>
</section>

<section class="home-search" aria-label="<?php echo esc_attr(nova_t('Recherche de biens')); ?>">
    <div class="container"><?php get_template_part('template-parts/search-bar'); ?></div>
</section>

<section class="section" aria-labelledby="featured-title">
    <div class="container">
        <div class="section-heading reveal">
            <div>
                <span class="eyebrow"><?php echo esc_html(nova_t('Sélection du moment')); ?></span>
                <h2 id="featured-title" class="section-heading__title"><?php echo esc_html(nova_t('Biens à la une')); ?></h2>
                <p class="lead"><?php echo esc_html(nova_t('Une sélection de biens récemment confiés à notre agence, visités et vérifiés par nos conseillers.')); ?></p>
            </div>
            <div class="section-heading__action"><a href="<?php echo esc_url($archive); ?>" class="link-arrow"><?php echo esc_html(nova_t('Voir tous les biens')); ?> <?php echo nova_icon('arrow', 16, 'rtl-flip'); ?></a></div>
        </div>
        <?php if ($featured) : ?>
            <div class="property-grid">
                <?php foreach ($featured as $i => $p) get_template_part('template-parts/property-card', null, ['p' => $p, 'class' => 'reveal reveal-delay-' . ($i % 3)]); ?>
            </div>
        <?php else : ?>
            <p class="empty-state"><?php echo esc_html(nova_t('Aucun bien publié pour le moment.')); ?></p>
        <?php endif; ?>
    </div>
</section>

<section class="section section--white" aria-labelledby="why-title">
    <div class="container why">
        <div class="why__intro reveal">
            <span class="eyebrow"><?php echo esc_html(nova_t('Pourquoi nous choisir ?')); ?></span>
            <h2 id="why-title"><?php echo esc_html(nova_t('Un accompagnement exigeant, du premier appel à la remise des clés.')); ?></h2>
            <p class="lead"><?php echo esc_html(nova_t('Acheter, vendre ou louer un bien en Algérie demande du temps, des vérifications et de la confiance. Nous prenons tout cela en charge.')); ?></p>
            <a href="<?php echo esc_url(home_url('/a-propos/')); ?>" class="link-arrow"><?php echo esc_html(nova_t('Notre approche')); ?> <?php echo nova_icon('arrow', 16, 'rtl-flip'); ?></a>
        </div>
        <ul class="why__grid">
            <?php foreach ($values as $i => [$icon, $title, $text]) : ?>
                <li class="why__item reveal reveal-delay-<?php echo $i; ?>">
                    <span class="why__icon"><?php echo nova_icon($icon, 26); ?></span>
                    <h3><?php echo esc_html(nova_t($title)); ?></h3>
                    <p><?php echo esc_html(nova_t($text)); ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<section class="section" aria-labelledby="cat-title">
    <div class="container">
        <h2 id="cat-title" class="type-row__title reveal"><?php echo esc_html(nova_t('Rechercher par type de bien')); ?></h2>
        <div class="type-row" data-type-row>
            <ul class="type-row__list" data-type-list>
                <?php foreach ($categories as [$type, $text, $scene]) : $n = nova_count_type($type); $label = nova_t(nova_type_plural_labels()[$type]); ?>
                    <li>
                        <a href="<?php echo esc_url(add_query_arg('bien', $type, $archive)); ?>" class="type-card">
                            <?php echo nova_image(['id' => 0, 'alt' => '', 'scene' => $scene, 'seed' => "cat-{$type}"], 'nova-card', 'type-card__img'); ?>
                            <span class="type-card__name"><?php echo esc_html($label); ?></span>
                            <span class="type-card__count"><?php echo esc_html($n . ' ' . nova_t($n > 1 ? 'biens' : 'bien')); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="type-row__next" data-type-next aria-label="<?php echo esc_attr(nova_t('Suivant')); ?>" hidden><?php echo nova_icon('chevronRight', 22, 'rtl-flip'); ?></button>
        </div>
    </div>
</section>

<section class="section section--beige" aria-labelledby="about-title">
    <div class="container about-teaser">
        <div class="about-teaser__media reveal">
            <?php echo nova_setting_image('about_image', 'living', nova_t('Intérieur élégant sélectionné par notre agence'), 'ratio-4-5'); ?>
            <div class="about-teaser__badge"><strong><bdi><?php echo esc_html(nova_setting('stat1_value')); ?></bdi></strong><span><?php echo esc_html(nova_s('stat1_label')); ?></span></div>
        </div>
        <div class="about-teaser__content reveal reveal-delay-1">
            <span class="eyebrow"><?php echo esc_html(nova_t('L’agence')); ?></span>
            <h2 id="about-title"><?php echo esc_html(nova_s('about_title')); ?></h2>
            <p class="lead"><?php echo esc_html(nova_s('about_text')); ?></p>
            <?php get_template_part('template-parts/stats'); ?>
            <a href="<?php echo esc_url(home_url('/a-propos/')); ?>" class="btn btn--primary"><?php echo esc_html(nova_t('En savoir plus sur notre agence')); ?> <?php echo nova_icon('arrow', 18, 'rtl-flip'); ?></a>
        </div>
    </div>
</section>

<?php get_template_part('template-parts/cta-banner'); ?>
<?php get_footer();
