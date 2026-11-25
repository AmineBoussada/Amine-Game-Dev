<?php
defined('ABSPATH') || exit;
get_header();
$featured = bahia_featured_properties(5);
$archive = get_post_type_archive_link('property');
$big = $featured[0] ?? null;
$side = array_slice($featured, 1, 2);
$wide = array_slice($featured, 3, 2);
$communes = bahia_top_communes(5);
$steps = [
    ['Nous vous écoutons', 'Budget, quartier, nombre de pièces, délais : votre projet tient sur une page.'],
    ['Nous visitons pour vous', 'Nous écartons les biens qui ne correspondent pas avant de vous les montrer.'],
    ['Nous suivons le dossier', 'Documents, livret foncier, compromis : jusqu’à la signature chez le notaire.'],
];
$wa = bahia_whatsapp_link(sprintf(bahia_t('Bonjour %s, je souhaite faire estimer mon bien.'), bahia_t('Bahia Immobilier')));
?>
<section class="rv-hero" aria-labelledby="hero-title">
    <div class="rv-hero__media">
        <?php echo bahia_setting_image('hero_image', 'villa', bahia_t('Villa blanche face à la mer'), 'rv-hero__img', true); ?>
        <?php echo bahia_stripes('rv-stripes--corner rv-stripes--top-end rv-stripes--lg'); ?>
    </div>
    <div class="rv-hero__panel">
        <span class="rv-eyebrow rv-eyebrow--rule"><?php echo esc_html(bahia_t('Oran') . ' · ' . bahia_t('Agence immobilière')); ?></span>
        <h1 id="hero-title" class="rv-h1"><?php echo esc_html(bahia_s('hero_title')); ?></h1>
        <p class="rv-lead"><?php echo esc_html(bahia_s('hero_text')); ?></p>
    </div>
</section>

<div class="rv-wrap rv-home-search"><?php get_template_part('template-parts/search-bar'); ?></div>

<section class="rv-wrap rv-section" aria-labelledby="sel-title">
    <div class="rv-sechead">
        <div>
            <span class="rv-eyebrow"><?php echo esc_html(bahia_t('La sélection')); ?></span>
            <h2 id="sel-title" class="rv-h2"><?php echo esc_html(bahia_t('Biens choisis cette semaine')); ?></h2>
        </div>
        <a class="rv-link" href="<?php echo esc_url($archive); ?>"><?php echo esc_html(bahia_t('Voir tous les biens')); ?> <span class="rv-arrow" aria-hidden="true"></span></a>
    </div>
    <?php if ($big) : ?>
        <div class="rv-mag property-grid">
            <?php get_template_part('template-parts/property-card', null, ['p' => $big, 'size' => 'big', 'class' => 'rv-mag__big']); ?>
            <?php if ($side) : ?>
                <div class="rv-mag__side">
                    <?php foreach ($side as $p) get_template_part('template-parts/property-card', null, ['p' => $p]); ?>
                </div>
            <?php endif; ?>
            <?php foreach ($wide as $p) get_template_part('template-parts/property-card', null, ['p' => $p, 'size' => 'wide', 'class' => 'rv-mag__wide']); ?>
        </div>
    <?php else : ?>
        <p class="rv-empty-line"><?php echo esc_html(bahia_t('Aucun bien publié pour le moment.')); ?></p>
    <?php endif; ?>
</section>

<?php if ($communes) : ?>
<section id="quartiers" class="rv-alt" aria-labelledby="q-title">
    <div class="rv-wrap rv-section">
        <div class="rv-split">
            <div>
                <span class="rv-eyebrow"><?php echo esc_html(bahia_t('Les quartiers')); ?></span>
                <h2 id="q-title" class="rv-h2"><?php echo esc_html(sprintf(bahia_t('%s, quartier par quartier'), bahia_t('Oran'))); ?></h2>
            </div>
            <p class="rv-lead"><?php echo esc_html(bahia_t('Nous connaissons chaque quartier rue par rue : l’exposition, le bruit, les écoles, le stationnement. Nous vous le disons avant la visite.')); ?></p>
        </div>
        <div class="rv-areas">
            <?php foreach ($communes as $c) : ?>
                <a href="<?php echo esc_url(add_query_arg('commune', $c['name'], $archive)); ?>" class="rv-area">
                    <span class="rv-area__media"><?php echo bahia_image($c['image'], 'bahia-card', 'rv-area__img', false, ''); ?></span>
                    <span class="rv-area__name"><?php echo esc_html(bahia_t($c['name'])); ?></span>
                    <span class="rv-rule" aria-hidden="true"></span>
                    <span class="rv-area__count"><?php echo esc_html($c['count'] . ' ' . bahia_t($c['count'] > 1 ? 'biens' : 'bien')); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section id="methode" class="rv-band" aria-labelledby="m-title">
    <?php echo bahia_stripes('rv-stripes--band'); ?>
    <div class="rv-wrap rv-section">
        <div class="rv-band__head">
            <span class="rv-eyebrow rv-eyebrow--gold"><?php echo esc_html(bahia_t('Notre méthode')); ?></span>
            <h2 id="m-title" class="rv-h2"><?php echo esc_html(bahia_t('Un seul conseiller, du premier appel à la remise des clés.')); ?></h2>
        </div>
        <ol class="rv-steps">
            <?php foreach ($steps as $i => [$title, $text]) : ?>
                <li><span class="rv-steps__n"><?php echo ['I', 'II', 'III'][$i]; ?></span><h3><?php echo esc_html(bahia_t($title)); ?></h3><p><?php echo esc_html(bahia_t($text)); ?></p></li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>

<section id="estimation" class="rv-wrap rv-section" aria-labelledby="e-title">
    <div class="rv-split rv-split--center">
        <div class="rv-stack">
            <span class="rv-eyebrow rv-eyebrow--rule"><?php echo esc_html(bahia_t('Propriétaires')); ?></span>
            <h2 id="e-title" class="rv-h2"><?php echo esc_html(bahia_t('Que vaut votre bien aujourd’hui ?')); ?></h2>
            <p class="rv-lead"><?php echo esc_html(bahia_t('Un conseiller visite votre bien, le compare aux ventes récentes du quartier et vous propose un prix réaliste, sans engagement.')); ?></p>
            <div class="rv-actions">
                <a href="<?php echo esc_url(bahia_contact_url('estimation', 'formulaire')); ?>" class="rv-btn"><?php echo esc_html(bahia_t('Demander une estimation')); ?></a>
                <a href="<?php echo esc_url($wa); ?>" class="rv-btn rv-btn--ghost" target="_blank" rel="noopener noreferrer"><?php echo bahia_icon('whatsapp', 18); ?> <?php echo esc_html(bahia_t('WhatsApp')); ?></a>
            </div>
        </div>
        <?php echo bahia_setting_image('about_image', 'living', bahia_t('Séjour avec baie vitrée sur la mer'), 'rv-estimate__img'); ?>
    </div>
</section>
<?php get_footer();
