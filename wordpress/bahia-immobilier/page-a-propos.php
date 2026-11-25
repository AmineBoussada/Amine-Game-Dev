<?php
defined('ABSPATH') || exit;
get_header();
the_post();
$steps = [
    ['Nous vous écoutons', 'Budget, quartier, nombre de pièces, délais : votre projet tient sur une page.'],
    ['Nous visitons pour vous', 'Nous écartons les biens qui ne correspondent pas avant de vous les montrer.'],
    ['Nous suivons le dossier', 'Documents, livret foncier, compromis : jusqu’à la signature chez le notaire.'],
];
$values = [
    ['Des biens vérifiés', 'Chaque bien est visité et ses documents contrôlés avant d’être proposé.'],
    ['Un conseiller dédié', 'Le même interlocuteur de la première visite jusqu’à la signature.'],
    ['Des prix réalistes', 'Nos estimations s’appuient sur les ventes récentes du quartier.'],
    ['Des honoraires annoncés', 'Nos honoraires sont communiqués dès le premier rendez-vous.'],
];
get_template_part('template-parts/page-header', null, [
    'title' => bahia_t('L’agence'),
    'subtitle' => bahia_s('description'),
    'crumbs' => [[bahia_t('L’agence'), null]],
]);
?>
<section class="rv-wrap rv-section rv-section--top0">
    <div class="rv-split rv-split--center">
        <?php echo bahia_setting_image('about_image', 'living', bahia_t('Séjour avec baie vitrée sur la mer'), 'rv-estimate__img'); ?>
        <div class="rv-stack">
            <span class="rv-eyebrow rv-eyebrow--rule"><?php echo esc_html(bahia_t('Notre mission')); ?></span>
            <h2 class="rv-h2"><?php echo esc_html(bahia_s('about_title')); ?></h2>
            <p class="rv-lead"><?php echo esc_html(bahia_s('about_text')); ?></p>
            <?php if (!bahia_is_ar() && trim(get_the_content())) : ?><div class="rv-prose"><?php the_content(); ?></div><?php endif; ?>
            <dl class="rv-stats">
                <?php for ($i = 1; $i <= 3; $i++) : ?>
                    <div><dt><?php echo esc_html(bahia_s("stat{$i}_label")); ?></dt><dd><bdi><?php echo esc_html(bahia_setting("stat{$i}_value")); ?></bdi></dd></div>
                <?php endfor; ?>
            </dl>
        </div>
    </div>
</section>

<section class="rv-band" aria-labelledby="m-title">
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

<section class="rv-wrap rv-section" aria-labelledby="v-title">
    <div class="rv-sechead">
        <div>
            <span class="rv-eyebrow"><?php echo esc_html(bahia_t('Nos engagements')); ?></span>
            <h2 id="v-title" class="rv-h2"><?php echo esc_html(bahia_t('Ce que nous vous garantissons')); ?></h2>
        </div>
        <a class="rv-link" href="<?php echo esc_url(bahia_contact_url('', 'formulaire')); ?>"><?php echo esc_html(bahia_t('Prendre rendez-vous')); ?> <span class="rv-arrow" aria-hidden="true"></span></a>
    </div>
    <ul class="rv-values">
        <?php foreach ($values as [$title, $text]) : ?>
            <li><h3><?php echo esc_html(bahia_t($title)); ?></h3><p><?php echo esc_html(bahia_t($text)); ?></p></li>
        <?php endforeach; ?>
    </ul>
</section>
<?php get_footer();
