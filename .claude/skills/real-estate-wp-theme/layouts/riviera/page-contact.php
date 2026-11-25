<?php
defined('ABSPATH') || exit;
get_header();
$aliases = ['recherche' => 'Recherche personnalisée', 'vendre' => 'Vendre ou louer mon bien', 'estimation' => 'Estimation de mon bien', 'visite' => 'Demande de visite'];
$sujet = sanitize_text_field(wp_unslash($_GET['sujet'] ?? ''));
$subject = $aliases[$sujet] ?? (in_array($sujet, nova_subjects(), true) ? $sujet : '');
$wa_msg = sprintf(nova_t('Bonjour %s, je souhaite avoir des informations.'), nova_t('NOVA IMMOBILIER'));
$cards = [
    ['phone', 'Téléphone', nova_setting('phone'), nova_tel_href(nova_setting('phone')), true],
    ['whatsapp', 'WhatsApp', nova_setting('mobile'), nova_whatsapp_link($wa_msg), true],
    ['mail', 'Email', nova_setting('email'), 'mailto:' . nova_setting('email'), true],
    ['pin', 'Agence', nova_s('city'), nova_maps_link(nova_setting('city') . ', Algérie'), false],
];
get_template_part('template-parts/page-header', null, [
    'title' => nova_t('Contactez-nous'),
    'subtitle' => nova_t('Une question, un projet d’achat, de vente ou de location ? Un conseiller vous répond rapidement.'),
    'crumbs' => [[nova_t('Contact'), null]],
]);
?>
<section class="rv-wrap rv-section rv-section--top0">
    <ul class="rv-contact-cards">
        <?php foreach ($cards as [$icon, $title, $value, $href, $ltr]) : ?>
            <li>
                <a href="<?php echo esc_attr($href); ?>" class="rv-contact-card"<?php echo in_array($icon, ['whatsapp', 'pin'], true) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
                    <?php echo nova_icon($icon, 22); ?>
                    <span class="rv-label"><?php echo esc_html(nova_t($title)); ?></span>
                    <strong><?php echo $ltr ? '<bdi dir="ltr">' . esc_html($value) . '</bdi>' : esc_html($value); ?></strong>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    <div class="rv-contact">
        <div id="formulaire" class="rv-stack">
            <span class="rv-eyebrow rv-eyebrow--rule"><?php echo esc_html(nova_t('Écrivez-nous')); ?></span>
            <h2 class="rv-h2"><?php echo esc_html(nova_t('Parlez-nous de votre projet')); ?></h2>
            <p class="rv-muted"><?php echo esc_html(nova_t('Les champs marqués d’un astérisque (*) sont obligatoires.')); ?></p>
            <?php get_template_part('template-parts/contact-form', null, ['subject' => $subject]); ?>
        </div>
        <aside class="rv-contact__aside" aria-label="<?php echo esc_attr(nova_t('Informations pratiques')); ?>">
            <div class="rv-box">
                <h3 class="rv-h4"><?php echo esc_html(nova_t('Horaires d’ouverture')); ?></h3>
                <ul class="rv-hours">
                    <?php foreach (nova_hours_i18n() as $h) : ?><li><span><?php echo esc_html($h['days']); ?></span><bdi><?php echo esc_html($h['time']); ?></bdi></li><?php endforeach; ?>
                </ul>
            </div>
            <div class="rv-box rv-box--band">
                <h3 class="rv-h4"><?php echo esc_html(nova_t('Vous souhaitez vendre ou louer votre bien ?')); ?></h3>
                <p><?php echo esc_html(nova_t('Bénéficiez d’une estimation gratuite et d’un plan de commercialisation sur mesure.')); ?></p>
                <a href="<?php echo esc_url(nova_contact_url('estimation', 'formulaire')); ?>" class="rv-link rv-link--light"><?php echo esc_html(nova_t('Demander une estimation')); ?></a>
            </div>
        </aside>
    </div>
</section>
<?php get_footer();
