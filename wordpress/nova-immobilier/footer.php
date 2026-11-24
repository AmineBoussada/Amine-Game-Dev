<?php defined('ABSPATH') || exit; ?>
</main>

<footer class="site-footer">
    <div class="container">
        <div class="site-footer__grid">
            <div class="site-footer__brand">
                <?php echo nova_logo(true); ?>
                <p><?php echo esc_html(nova_setting('description')); ?></p>
                <ul class="socials" aria-label="Réseaux sociaux">
                    <?php foreach (nova_socials() as $s) : ?>
                        <li><a href="<?php echo esc_url($s['url']); ?>" target="_blank" rel="noopener noreferrer" aria-label="NOVA IMMOBILIER sur <?php echo esc_attr($s['name']); ?>"><?php echo nova_icon($s['icon'], 18); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div>
                <h2 class="site-footer__title">Navigation</h2>
                <ul class="site-footer__links">
                    <?php foreach (nova_nav_items() as $item) : ?>
                        <li><a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['label']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div>
                <h2 class="site-footer__title">Nos biens</h2>
                <ul class="site-footer__links">
                    <?php foreach (nova_type_plural_labels() as $type => $label) : ?>
                        <li><a href="<?php echo esc_url(add_query_arg('bien', $type, get_post_type_archive_link('property'))); ?>"><?php echo esc_html($label); ?></a></li>
                    <?php endforeach; ?>
                    <li><a href="<?php echo esc_url(home_url('/favoris/')); ?>">Mes favoris</a></li>
                </ul>
            </div>

            <div>
                <h2 class="site-footer__title">Contact</h2>
                <ul class="site-footer__contact">
                    <li><?php echo nova_icon('pin', 18); ?><span><?php echo esc_html(nova_setting('address')); ?><br><?php echo esc_html(nova_setting('city')); ?></span></li>
                    <li><?php echo nova_icon('phone', 18); ?><a href="<?php echo esc_attr(nova_tel_href(nova_setting('phone'))); ?>"><?php echo esc_html(nova_setting('phone')); ?></a></li>
                    <li><?php echo nova_icon('whatsapp', 18); ?><a href="<?php echo esc_url(nova_whatsapp_link('Bonjour NOVA IMMOBILIER, je souhaite avoir des informations.')); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html(nova_setting('mobile')); ?></a></li>
                    <li><?php echo nova_icon('mail', 18); ?><a href="mailto:<?php echo esc_attr(nova_setting('email')); ?>"><?php echo esc_html(nova_setting('email')); ?></a></li>
                </ul>
            </div>
        </div>

        <div class="site-footer__bottom">
            <p>© <?php echo esc_html(wp_date('Y')); ?> NOVA IMMOBILIER. Tous droits réservés.</p>
            <?php if (nova_setting('demo_notice')) : ?><p class="site-footer__demo">Site de démonstration — biens, prix et coordonnées fictifs.</p><?php endif; ?>
            <a href="<?php echo esc_url(get_privacy_policy_url() ?: home_url('/politique-de-confidentialite/')); ?>">Politique de confidentialité</a>
        </div>
    </div>
</footer>

<?php if (!is_singular('property')) : ?>
    <a class="wa-float" href="<?php echo esc_url(nova_whatsapp_link('Bonjour NOVA IMMOBILIER, je souhaite avoir des informations.')); ?>" target="_blank" rel="noopener noreferrer" aria-label="Discuter avec nous sur WhatsApp"><?php echo nova_icon('whatsapp', 26); ?></a>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
