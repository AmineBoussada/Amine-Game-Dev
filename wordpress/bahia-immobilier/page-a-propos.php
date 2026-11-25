<?php
defined('ABSPATH') || exit;
get_header();
the_post();
$steps = [
    ['Écoute', 'Nous prenons le temps de comprendre votre projet, votre budget et vos priorités.'],
    ['Sélection', 'Nous identifions et visitons les biens qui correspondent réellement à vos critères.'],
    ['Vérification', 'Actes, livret foncier, situation juridique : chaque dossier est contrôlé.'],
    ['Signature', 'Nous vous accompagnons jusqu’à la signature chez le notaire et la remise des clés.'],
];
$values = [
    ['selection', 'Sélection de biens', 'Chaque bien est visité, vérifié et documenté avant d’être proposé à nos clients.'],
    ['support', 'Accompagnement personnalisé', 'Un conseiller dédié vous suit de la première visite jusqu’à la signature chez le notaire.'],
    ['map', 'Expertise locale', 'Une connaissance fine des quartiers d’Oran et des prix réels du marché algérien.'],
    ['shield', 'Transparence et confiance', 'Des informations claires, des documents vérifiés et des honoraires annoncés dès le départ.'],
];
$team = [['CT', 'Conseiller transaction', 'Vente & achat résidentiel'], ['CL', 'Conseillère location', 'Location & gestion locative'], ['RE', 'Responsable entreprises', 'Locaux & immobilier d’entreprise']];
get_template_part('template-parts/page-header', null, ['title' => sprintf(bahia_t('À propos de %s'), bahia_t('Bahia Immobilier')), 'subtitle' => bahia_t('Une agence oranaise, une exigence européenne.'), 'crumbs' => [[bahia_t('À propos'), null]]]);
?>
<section class="section">
    <div class="container about-teaser">
        <div class="about-teaser__media reveal"><?php echo bahia_setting_image('about_image', 'living', bahia_t('Intérieur contemporain lumineux'), 'ratio-4-5'); ?></div>
        <div class="about-teaser__content reveal reveal-delay-1">
            <span class="eyebrow"><?php echo esc_html(bahia_t('Notre mission')); ?></span>
            <h2><?php echo esc_html(bahia_s('about_title')); ?></h2>
            <p class="lead"><?php echo esc_html(bahia_s('about_text')); ?></p>
            <?php if (!bahia_is_ar() && trim(get_the_content())) : ?><div class="muted"><?php the_content(); ?></div><?php endif; ?>
            <?php get_template_part('template-parts/stats'); ?>
        </div>
    </div>
</section>
<section class="section section--white">
    <div class="container">
        <div class="section-heading section-heading--center reveal"><div><span class="eyebrow"><?php echo esc_html(bahia_t('Notre méthode')); ?></span><h2 class="section-heading__title"><?php echo esc_html(bahia_t('Quatre étapes, un seul interlocuteur')); ?></h2></div></div>
        <ol class="steps">
            <?php foreach ($steps as $i => [$title, $text]) : ?>
                <li class="reveal reveal-delay-<?php echo $i; ?>"><span class="steps__n"><?php echo sprintf('%02d', $i + 1); ?></span><h3><?php echo esc_html(bahia_t($title)); ?></h3><p><?php echo esc_html(bahia_t($text)); ?></p></li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>
<section class="section">
    <div class="container">
        <div class="section-heading reveal"><div><span class="eyebrow"><?php echo esc_html(bahia_t('Nos engagements')); ?></span><h2 class="section-heading__title"><?php echo esc_html(bahia_t('Ce qui nous distingue')); ?></h2></div></div>
        <ul class="why__grid why__grid--4">
            <?php foreach ($values as [$icon, $title, $text]) : ?>
                <li class="why__item reveal"><span class="why__icon"><?php echo bahia_icon($icon, 26); ?></span><h3><?php echo esc_html(bahia_t($title)); ?></h3><p><?php echo esc_html(bahia_t($text)); ?></p></li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
<section class="section section--beige">
    <div class="container">
        <div class="section-heading reveal">
            <div><span class="eyebrow"><?php echo esc_html(bahia_t('L’équipe')); ?></span><h2 class="section-heading__title"><?php echo esc_html(bahia_t('Des conseillers spécialisés')); ?></h2><p class="lead"><?php echo esc_html(bahia_t('Chaque projet est suivi par un conseiller dédié selon la nature de votre recherche.')); ?></p></div>
            <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="link-arrow"><?php echo esc_html(bahia_t('Prendre rendez-vous')); ?> <?php echo bahia_icon('arrow', 16, 'rtl-flip'); ?></a>
        </div>
        <ul class="team">
            <?php foreach ($team as $i => [$ini, $name, $role]) : ?>
                <li class="team__card reveal reveal-delay-<?php echo $i; ?>"><span class="team__avatar" aria-hidden="true"><?php echo esc_html($ini); ?></span><h3><?php echo esc_html(bahia_t($name)); ?></h3><p><?php echo esc_html(bahia_t($role)); ?></p></li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
<?php get_template_part('template-parts/cta-banner'); ?>
<?php get_footer();
