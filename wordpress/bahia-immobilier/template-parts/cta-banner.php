<?php defined('ABSPATH') || exit; ?>
<section class="cta-banner" aria-labelledby="cta-title">
    <div class="container">
        <div class="cta-banner__inner reveal">
            <div>
                <span class="eyebrow"><?php echo esc_html(bahia_t('Parlons de votre projet')); ?></span>
                <h2 id="cta-title"><?php echo esc_html(bahia_t('Vous avez un projet immobilier ?')); ?></h2>
                <p><?php echo esc_html(bahia_t('Notre équipe est à votre écoute pour vous accompagner.')); ?></p>
            </div>
            <div class="cta-banner__actions">
                <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="btn btn--light"><?php echo esc_html(bahia_t('Contactez-nous')); ?> <?php echo bahia_icon('arrow', 18, 'rtl-flip'); ?></a>
                <a href="<?php echo esc_url(bahia_whatsapp_link(sprintf(bahia_t('Bonjour %s, j’ai un projet immobilier et je souhaite être accompagné(e).'), bahia_t('Bahia Immobilier')))); ?>" class="btn btn--whatsapp" target="_blank" rel="noopener noreferrer"><?php echo bahia_icon('whatsapp', 18); ?> <?php echo esc_html(bahia_t('WhatsApp')); ?></a>
            </div>
        </div>
    </div>
</section>
