<?php
defined('ABSPATH') || exit;
$archive = get_post_type_archive_link('property');
$wa_msg = sprintf(bahia_t('Bonjour %s, je souhaite avoir des informations.'), bahia_t('Bahia Immobilier'));
?>
</main>

<footer class="rv-footer">
    <?php echo bahia_stripes('rv-stripes--footer'); ?>
    <div class="rv-wrap rv-footer__inner">
        <div class="rv-footer__grid">
            <div class="rv-footer__brand">
                <?php echo bahia_rv_logo(true); ?>
                <p><?php echo esc_html(bahia_s('description')); ?></p>
                <?php if ($socials = bahia_socials()) : ?>
                    <ul class="rv-socials" aria-label="<?php echo esc_attr(bahia_t('Réseaux sociaux')); ?>">
                        <?php foreach ($socials as $s) : ?>
                            <li><a href="<?php echo esc_url($s['url']); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr($s['name']); ?>"><?php echo bahia_icon($s['icon'], 18); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <div>
                <h2 class="rv-footer__title"><?php echo esc_html(bahia_t('Nos biens')); ?></h2>
                <ul class="rv-footer__links">
                    <li><a href="<?php echo esc_url(add_query_arg('transaction', 'vente', $archive)); ?>"><?php echo esc_html(bahia_t('Acheter')); ?></a></li>
                    <li><a href="<?php echo esc_url(add_query_arg('transaction', 'location', $archive)); ?>"><?php echo esc_html(bahia_t('Louer')); ?></a></li>
                    <?php foreach (bahia_type_plural_labels() as $type => $label) : ?>
                        <li><a href="<?php echo esc_url(add_query_arg('bien', $type, $archive)); ?>"><?php echo esc_html(bahia_t($label)); ?></a></li>
                    <?php endforeach; ?>
                    <li><a href="<?php echo esc_url(home_url('/favoris/')); ?>"><?php echo esc_html(bahia_t('Mes favoris')); ?></a></li>
                </ul>
            </div>
            <div>
                <h2 class="rv-footer__title"><?php echo esc_html(bahia_t('Contact')); ?></h2>
                <ul class="rv-footer__links">
                    <li><a href="<?php echo esc_attr(bahia_tel_href(bahia_setting('phone'))); ?>"><bdi dir="ltr"><?php echo esc_html(bahia_setting('phone')); ?></bdi></a></li>
                    <li><a href="<?php echo esc_url(bahia_whatsapp_link($wa_msg)); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html(bahia_t('WhatsApp')); ?> · <bdi dir="ltr"><?php echo esc_html(bahia_setting('mobile')); ?></bdi></a></li>
                    <li><a href="mailto:<?php echo esc_attr(bahia_setting('email')); ?>"><?php echo esc_html(bahia_setting('email')); ?></a></li>
                    <li><?php echo esc_html(bahia_s('address')); ?><br><?php echo esc_html(bahia_s('city')); ?></li>
                </ul>
            </div>
            <div>
                <h2 class="rv-footer__title"><?php echo esc_html(bahia_t('Horaires')); ?></h2>
                <ul class="rv-footer__links">
                    <?php foreach (bahia_hours_i18n() as $h) : ?>
                        <li><?php echo esc_html($h['days']); ?> · <bdi><?php echo esc_html($h['time']); ?></bdi></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <div class="rv-footer__bottom">
            <p>© <?php echo esc_html(wp_date('Y')); ?> <?php echo esc_html(bahia_t('Bahia Immobilier')); ?>. <?php echo esc_html(bahia_t('Tous droits réservés.')); ?></p>
            <?php if (bahia_setting('demo_notice')) : ?><p><?php echo esc_html(bahia_t('Site de démonstration — biens, prix et coordonnées fictifs.')); ?></p><?php endif; ?>
            <a href="<?php echo esc_url(get_privacy_policy_url() ?: home_url('/politique-de-confidentialite/')); ?>"><?php echo esc_html(bahia_t('Politique de confidentialité')); ?></a>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
