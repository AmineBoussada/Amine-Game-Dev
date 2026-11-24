<?php
defined('ABSPATH') || exit;
get_header();
$aliases = ['recherche' => 'Recherche personnalisée', 'vendre' => 'Vendre ou louer mon bien', 'estimation' => 'Estimation de mon bien', 'visite' => 'Demande de visite'];
$sujet = sanitize_text_field(wp_unslash($_GET['sujet'] ?? ''));
$subject = $aliases[$sujet] ?? (in_array($sujet, nova_subjects(), true) ? $sujet : '');
$cards = [
    ['phone', 'Téléphone', nova_setting('phone'), nova_tel_href(nova_setting('phone')), 'Du dimanche au jeudi', false],
    ['whatsapp', 'WhatsApp', nova_setting('mobile'), nova_whatsapp_link('Bonjour NOVA IMMOBILIER, je souhaite avoir des informations.'), 'Réponse rapide', true],
    ['mail', 'Email', nova_setting('email'), 'mailto:' . nova_setting('email'), 'Réponse sous 24 h ouvrées', false],
    ['pin', 'Agence', nova_setting('city'), nova_maps_link(nova_setting('city') . ', Algérie'), nova_setting('address'), true],
];
get_template_part('template-parts/page-header', null, [
    'title' => 'Contactez-nous',
    'subtitle' => 'Une question, un projet d’achat, de vente ou de location ? Notre équipe vous répond rapidement.',
    'crumbs' => [['Contact', null]],
]);
?>
<section class="section contact">
    <div class="container">
        <ul class="contact__cards">
            <?php foreach ($cards as $i => [$icon, $title, $value, $href, $note, $ext]) : ?>
                <li class="reveal reveal-delay-<?php echo $i; ?>">
                    <a href="<?php echo esc_attr($href); ?>" class="contact-card"<?php echo $ext ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
                        <span class="contact-card__icon<?php echo $icon === 'whatsapp' ? ' is-wa' : ''; ?>"><?php echo nova_icon($icon, 22); ?></span>
                        <span class="contact-card__title"><?php echo esc_html($title); ?></span>
                        <strong><?php echo esc_html($value); ?></strong>
                        <span class="contact-card__note"><?php echo esc_html($note); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="contact__layout">
            <div class="contact__form-wrap" id="formulaire">
                <span class="eyebrow">Écrivez-nous</span>
                <h2>Parlez-nous de votre projet</h2>
                <p class="muted">Tous les champs marqués d’un astérisque (*) sont obligatoires.</p>
                <?php get_template_part('template-parts/contact-form', null, ['subject' => $subject]); ?>
            </div>
            <aside class="contact__aside reveal reveal-delay-1" aria-label="Informations pratiques">
                <div class="contact__box">
                    <h3>Horaires d’ouverture</h3>
                    <ul class="hours">
                        <?php foreach (nova_hours() as $h) : ?><li><span><?php echo esc_html($h['days']); ?></span><strong><?php echo esc_html($h['time']); ?></strong></li><?php endforeach; ?>
                    </ul>
                </div>
                <div class="contact__box contact__box--dark">
                    <h3>Vous souhaitez vendre ou louer votre bien ?</h3>
                    <p>Bénéficiez d’une estimation gratuite et d’un plan de commercialisation sur mesure.</p>
                    <a href="<?php echo esc_url(nova_contact_url('estimation', 'formulaire')); ?>" class="link-arrow">Demander une estimation <?php echo nova_icon('arrow', 16); ?></a>
                </div>
                <div class="contact__box">
                    <h3>Suivez-nous</h3>
                    <ul class="socials socials--dark">
                        <?php foreach (nova_socials() as $s) : ?><li><a href="<?php echo esc_url($s['url']); ?>" target="_blank" rel="noopener noreferrer" aria-label="NOVA IMMOBILIER sur <?php echo esc_attr($s['name']); ?>"><?php echo nova_icon($s['icon'], 18); ?></a></li><?php endforeach; ?>
                    </ul>
                </div>
            </aside>
        </div>
    </div>
</section>
<?php get_footer();
