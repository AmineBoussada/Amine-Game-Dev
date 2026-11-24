<?php defined('ABSPATH') || exit; ?>
<section class="cta-banner" aria-labelledby="cta-title">
    <div class="container">
        <div class="cta-banner__inner reveal">
            <div>
                <span class="eyebrow">Parlons de votre projet</span>
                <h2 id="cta-title">Vous avez un projet immobilier ?</h2>
                <p>Notre équipe est à votre écoute pour vous accompagner.</p>
            </div>
            <div class="cta-banner__actions">
                <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="btn btn--light">Contactez-nous <?php echo nova_icon('arrow', 18); ?></a>
                <a href="<?php echo esc_url(nova_whatsapp_link('Bonjour NOVA IMMOBILIER, j’ai un projet immobilier et je souhaite être accompagné(e).')); ?>" class="btn btn--whatsapp" target="_blank" rel="noopener noreferrer"><?php echo nova_icon('whatsapp', 18); ?> WhatsApp</a>
            </div>
        </div>
    </div>
</section>
