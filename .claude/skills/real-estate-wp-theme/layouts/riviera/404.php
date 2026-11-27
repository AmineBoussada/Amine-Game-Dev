<?php
defined('ABSPATH') || exit;
get_header();
?>
<section class="rv-wrap rv-section">
    <div class="rv-empty">
        <span class="rv-404" aria-hidden="true">404</span>
        <h1 class="rv-h2"><?php echo esc_html(nova_t('Cette page est introuvable')); ?></h1>
        <p><?php echo esc_html(nova_t('Le bien ou la page que vous cherchez n’existe plus ou a été déplacé.')); ?></p>
        <div class="rv-actions rv-actions--center">
            <a href="<?php echo esc_url(get_post_type_archive_link('property')); ?>" class="rv-btn"><?php echo esc_html(nova_t('Voir nos biens')); ?></a>
            <a href="<?php echo esc_url(home_url('/')); ?>" class="rv-btn rv-btn--ghost"><?php echo esc_html(nova_t('Retour à l’accueil')); ?></a>
        </div>
    </div>
</section>
<?php get_footer();
