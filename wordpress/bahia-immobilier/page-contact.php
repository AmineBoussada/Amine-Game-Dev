<?php
defined('ABSPATH') || exit;
if (bahia_render_elementor_page()) {
    return; // page built with Elementor
}
get_header();
$aliases = ['recherche' => 'Recherche personnalisée', 'vendre' => 'Vendre ou louer mon bien', 'estimation' => 'Estimation de mon bien', 'visite' => 'Demande de visite'];
$sujet = sanitize_text_field(wp_unslash($_GET['sujet'] ?? ''));
$subject = $aliases[$sujet] ?? (in_array($sujet, bahia_subjects(), true) ? $sujet : '');
$cards = [
    ['phone', bahia_t('Téléphone'), bahia_setting('phone'), bahia_tel_href(bahia_setting('phone')), bahia_t('Du dimanche au jeudi'), false],
    ['whatsapp', bahia_t('WhatsApp'), bahia_setting('mobile'), bahia_whatsapp_link(sprintf(bahia_t('Bonjour %s, je souhaite avoir des informations.'), bahia_t('Bahia Immobilier'))), bahia_t('Réponse rapide'), true],
    ['mail', bahia_t('Email'), bahia_setting('email'), 'mailto:' . bahia_setting('email'), bahia_t('Réponse sous 24 h ouvrées'), false],
    ['pin', bahia_t('Agence'), bahia_s('city'), bahia_maps_link(bahia_setting('city') . ', Algérie'), bahia_s('address'), true],
];
get_template_part('template-parts/page-header', null, [
    'title' => bahia_t('Contactez-nous'),
    'subtitle' => bahia_t('Une question, un projet d’achat, de vente ou de location ? Notre équipe vous répond rapidement.'),
    'crumbs' => [[bahia_t('Contact'), null]],
]);
?>
<section class="section contact">
    <div class="container">
        <ul class="contact__cards">
            <?php foreach ($cards as $i => [$icon, $title, $value, $href, $note, $ext]) : ?>
                <li class="reveal reveal-delay-<?php echo $i; ?>">
                    <a href="<?php echo esc_attr($href); ?>" class="contact-card"<?php echo $ext ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
                        <span class="contact-card__icon<?php echo $icon === 'whatsapp' ? ' is-wa' : ''; ?>"><?php echo bahia_icon($icon, 22); ?></span>
                        <span class="contact-card__title"><?php echo esc_html($title); ?></span>
                        <strong><?php echo in_array($icon, ['phone', 'whatsapp', 'mail'], true) ? '<bdi dir="ltr">' . esc_html($value) . '</bdi>' : esc_html($value); ?></strong>
                        <span class="contact-card__note"><?php echo esc_html($note); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="contact__layout">
            <div class="contact__form-wrap" id="formulaire">
                <span class="eyebrow"><?php echo esc_html(bahia_t('Écrivez-nous')); ?></span>
                <h2><?php echo esc_html(bahia_t('Parlez-nous de votre projet')); ?></h2>
                <p class="muted"><?php echo esc_html(bahia_t('Les champs marqués d’un astérisque (*) sont obligatoires.')); ?></p>
                <?php get_template_part('template-parts/contact-form', null, ['subject' => $subject]); ?>
            </div>
            <aside class="contact__aside reveal reveal-delay-1" aria-label="<?php echo esc_attr(bahia_t('Informations pratiques')); ?>">
                <div class="contact__box">
                    <h3><?php echo esc_html(bahia_t('Horaires d’ouverture')); ?></h3>
                    <ul class="hours">
                        <?php foreach (bahia_hours_i18n() as $h) : ?><li><span><?php echo esc_html($h['days']); ?></span><strong><bdi><?php echo esc_html($h['time']); ?></bdi></strong></li><?php endforeach; ?>
                    </ul>
                </div>
                <div class="contact__box contact__box--dark">
                    <h3><?php echo esc_html(bahia_t('Vous souhaitez vendre ou louer votre bien ?')); ?></h3>
                    <p><?php echo esc_html(bahia_t('Bénéficiez d’une estimation gratuite et d’un plan de commercialisation sur mesure.')); ?></p>
                    <a href="<?php echo esc_url(bahia_contact_url('estimation', 'formulaire')); ?>" class="link-arrow"><?php echo esc_html(bahia_t('Demander une estimation')); ?> <?php echo bahia_icon('arrow', 16, 'rtl-flip'); ?></a>
                </div>
                <div class="contact__box">
                    <h3><?php echo esc_html(bahia_t('Suivez-nous')); ?></h3>
                    <ul class="socials socials--dark">
                        <?php foreach (bahia_socials() as $s) : ?><li><a href="<?php echo esc_url($s['url']); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr($s['name']); ?>"><?php echo bahia_icon($s['icon'], 18); ?></a></li><?php endforeach; ?>
                    </ul>
                </div>
            </aside>
        </div>
    </div>
</section>
<?php get_footer();
