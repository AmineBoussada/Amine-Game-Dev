<?php defined('ABSPATH') || exit; ?>
<section class="cta-banner" aria-labelledby="cta-title">
    <div class="container">
        <div class="cta-banner__inner reveal">
            <div>
                <span class="eyebrow"><?php echo esc_html(nova_t('Parlons de votre projet')); ?></span>
                <h2 id="cta-title"><?php echo esc_html(nova_t('Vous avez un projet immobilier ?')); ?></h2>
                <p><?php echo esc_html(nova_t('Notre équipe est à votre écoute pour vous accompagner.')); ?></p>
            </div>
            <div class="cta-banner__actions">
                <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="btn btn--light"><?php echo esc_html(nova_t('Contactez-nous')); ?> <?php echo nova_icon('arrow', 18, 'rtl-flip'); ?></a>
                <a href="<?php echo esc_url(nova_whatsapp_link(sprintf(nova_t('Bonjour %s, j’ai un projet immobilier et je souhaite être accompagné(e).'), nova_t('NOVA IMMOBILIER')))); ?>" class="btn btn--whatsapp" target="_blank" rel="noopener noreferrer"><?php echo nova_icon('whatsapp', 18); ?> <?php echo esc_html(nova_t('WhatsApp')); ?></a>
            </div>
        </div>
    </div>
</section>
