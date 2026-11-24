<?php
defined('ABSPATH') || exit;
get_header();
?>
<section class="not-found">
    <div class="container">
        <span class="not-found__code serif">404</span>
        <h1>Cette page est introuvable</h1>
        <p class="lead">Le bien ou la page que vous recherchez n’existe plus ou a été déplacé.</p>
        <div class="not-found__actions">
            <a href="<?php echo esc_url(get_post_type_archive_link('property')); ?>" class="btn btn--primary">Voir nos biens <?php echo nova_icon('arrow', 18); ?></a>
            <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn--outline">Retour à l’accueil</a>
        </div>
    </div>
</section>
<?php get_footer();
