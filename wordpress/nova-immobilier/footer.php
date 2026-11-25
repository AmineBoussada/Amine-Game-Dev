<?php defined('ABSPATH') || exit; ?>
</main>

<footer class="site-footer">
    <div class="container">
        <div class="site-footer__grid">
            <div class="site-footer__brand">
                <?php echo nova_logo(true); ?>
                <p><?php echo esc_html(nova_s('description')); ?></p>
                <ul class="socials" aria-label="<?php echo esc_attr(nova_t('Réseaux sociaux')); ?>">
                    <?php foreach (nova_socials() as $s) : ?>
                        <li><a href="<?php echo esc_url($s['url']); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr($s['name']); ?>"><?php echo nova_icon($s['icon'], 18); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div>
                <h2 class="site-footer__title"><?php echo esc_html(nova_t('Navigation')); ?></h2>
                <ul class="site-footer__links">
                    <?php foreach (nova_nav_items() as $item) : ?>
                        <li><a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html(nova_t($item['label'])); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div>
                <h2 class="site-footer__title"><?php echo esc_html(nova_t('Nos biens')); ?></h2>
                <ul class="site-footer__links">
                    <?php foreach (nova_type_plural_labels() as $type => $label) : ?>
                        <li><a href="<?php echo esc_url(add_query_arg('bien', $type, get_post_type_archive_link('property'))); ?>"><?php echo esc_html(nova_t($label)); ?></a></li>
                    <?php endforeach; ?>
                    <li><a href="<?php echo esc_url(home_url('/favoris/')); ?>"><?php echo esc_html(nova_t('Mes favoris')); ?></a></li>
                </ul>
            </div>

            <div>
                <h2 class="site-footer__title"><?php echo esc_html(nova_t('Contact')); ?></h2>
                <ul class="site-footer__contact">
                    <li><?php echo nova_icon('pin', 18); ?><span><?php echo esc_html(nova_s('address')); ?><br><?php echo esc_html(nova_s('city')); ?></span></li>
                    <li><?php echo nova_icon('phone', 18); ?><a href="<?php echo esc_attr(nova_tel_href(nova_setting('phone'))); ?>"><bdi dir="ltr"><?php echo esc_html(nova_setting('phone')); ?></bdi></a></li>
                    <li><?php echo nova_icon('whatsapp', 18); ?><a href="<?php echo esc_url(nova_whatsapp_link(sprintf(nova_t('Bonjour %s, je souhaite avoir des informations.'), nova_t('NOVA IMMOBILIER')))); ?>" target="_blank" rel="noopener noreferrer"><bdi dir="ltr"><?php echo esc_html(nova_setting('mobile')); ?></bdi></a></li>
                    <li><?php echo nova_icon('mail', 18); ?><a href="mailto:<?php echo esc_attr(nova_setting('email')); ?>"><?php echo esc_html(nova_setting('email')); ?></a></li>
                </ul>
            </div>
        </div>

        <div class="site-footer__bottom">
            <p>© <?php echo esc_html(wp_date('Y')); ?> <?php echo esc_html(nova_t('NOVA IMMOBILIER')); ?>. <?php echo esc_html(nova_t('Tous droits réservés.')); ?></p>
            <?php if (nova_setting('demo_notice')) : ?><p class="site-footer__demo"><?php echo esc_html(nova_t('Site de démonstration — biens, prix et coordonnées fictifs.')); ?></p><?php endif; ?>
            <a href="<?php echo esc_url(get_privacy_policy_url() ?: home_url('/politique-de-confidentialite/')); ?>"><?php echo esc_html(nova_t('Politique de confidentialité')); ?></a>
        </div>
    </div>
</footer>

<?php if (!is_singular('property')) : ?>
    <a class="wa-float" href="<?php echo esc_url(nova_whatsapp_link(sprintf(nova_t('Bonjour %s, je souhaite avoir des informations.'), nova_t('NOVA IMMOBILIER')))); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr(nova_t('Discuter avec nous sur WhatsApp')); ?>"><?php echo nova_icon('whatsapp', 26); ?></a>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
