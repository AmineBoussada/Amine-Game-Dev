<?php
defined('ABSPATH') || exit;
get_header();
?>
<section class="not-found">
    <div class="container">
        <span class="not-found__code serif">404</span>
        <h1><?php echo esc_html(bahia_t('Cette page est introuvable')); ?></h1>
        <p class="lead"><?php echo esc_html(bahia_t('Le bien ou la page que vous recherchez n’existe plus ou a été déplacé.')); ?></p>
        <div class="not-found__actions">
            <a href="<?php echo esc_url(get_post_type_archive_link('property')); ?>" class="btn btn--primary"><?php echo esc_html(bahia_t('Voir nos biens')); ?> <?php echo bahia_icon('arrow', 18, 'rtl-flip'); ?></a>
            <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn--outline"><?php echo esc_html(bahia_t('Retour à l’accueil')); ?></a>
        </div>
    </div>
</section>
<?php get_footer();
