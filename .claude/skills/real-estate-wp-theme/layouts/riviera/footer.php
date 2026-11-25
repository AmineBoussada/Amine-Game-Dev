<?php
defined('ABSPATH') || exit;
$archive = get_post_type_archive_link('property');
$wa_msg = sprintf(nova_t('Bonjour %s, je souhaite avoir des informations.'), nova_t('NOVA IMMOBILIER'));
?>
</main>

<footer class="rv-footer">
    <?php echo nova_stripes('rv-stripes--footer'); ?>
    <div class="rv-wrap rv-footer__inner">
        <div class="rv-footer__grid">
            <div class="rv-footer__brand">
                <?php echo nova_rv_logo(true); ?>
                <p><?php echo esc_html(nova_s('description')); ?></p>
                <?php if ($socials = nova_socials()) : ?>
                    <ul class="rv-socials" aria-label="<?php echo esc_attr(nova_t('Réseaux sociaux')); ?>">
                        <?php foreach ($socials as $s) : ?>
                            <li><a href="<?php echo esc_url($s['url']); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr($s['name']); ?>"><?php echo nova_icon($s['icon'], 18); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <div>
                <h2 class="rv-footer__title"><?php echo esc_html(nova_t('Nos biens')); ?></h2>
                <ul class="rv-footer__links">
                    <li><a href="<?php echo esc_url(add_query_arg('transaction', 'vente', $archive)); ?>"><?php echo esc_html(nova_t('Acheter')); ?></a></li>
                    <li><a href="<?php echo esc_url(add_query_arg('transaction', 'location', $archive)); ?>"><?php echo esc_html(nova_t('Louer')); ?></a></li>
                    <?php foreach (nova_type_plural_labels() as $type => $label) : ?>
                        <li><a href="<?php echo esc_url(add_query_arg('bien', $type, $archive)); ?>"><?php echo esc_html(nova_t($label)); ?></a></li>
                    <?php endforeach; ?>
                    <li><a href="<?php echo esc_url(home_url('/favoris/')); ?>"><?php echo esc_html(nova_t('Mes favoris')); ?></a></li>
                </ul>
            </div>
            <div>
                <h2 class="rv-footer__title"><?php echo esc_html(nova_t('Contact')); ?></h2>
                <ul class="rv-footer__links">
                    <li><a href="<?php echo esc_attr(nova_tel_href(nova_setting('phone'))); ?>"><bdi dir="ltr"><?php echo esc_html(nova_setting('phone')); ?></bdi></a></li>
                    <li><a href="<?php echo esc_url(nova_whatsapp_link($wa_msg)); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html(nova_t('WhatsApp')); ?> · <bdi dir="ltr"><?php echo esc_html(nova_setting('mobile')); ?></bdi></a></li>
                    <li><a href="mailto:<?php echo esc_attr(nova_setting('email')); ?>"><?php echo esc_html(nova_setting('email')); ?></a></li>
                    <li><?php echo esc_html(nova_s('address')); ?><br><?php echo esc_html(nova_s('city')); ?></li>
                </ul>
            </div>
            <div>
                <h2 class="rv-footer__title"><?php echo esc_html(nova_t('Horaires')); ?></h2>
                <ul class="rv-footer__links">
                    <?php foreach (nova_hours_i18n() as $h) : ?>
                        <li><?php echo esc_html($h['days']); ?> · <bdi><?php echo esc_html($h['time']); ?></bdi></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <div class="rv-footer__bottom">
            <p>© <?php echo esc_html(wp_date('Y')); ?> <?php echo esc_html(nova_t('NOVA IMMOBILIER')); ?>. <?php echo esc_html(nova_t('Tous droits réservés.')); ?></p>
            <?php if (nova_setting('demo_notice')) : ?><p><?php echo esc_html(nova_t('Site de démonstration — biens, prix et coordonnées fictifs.')); ?></p><?php endif; ?>
            <a href="<?php echo esc_url(get_privacy_policy_url() ?: home_url('/politique-de-confidentialite/')); ?>"><?php echo esc_html(nova_t('Politique de confidentialité')); ?></a>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
