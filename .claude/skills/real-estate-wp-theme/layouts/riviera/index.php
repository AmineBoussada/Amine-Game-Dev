<?php
/** Fallback template (blog posts, search results). */
defined('ABSPATH') || exit;
get_header();
$title = is_search() ? nova_t('Résultats de recherche') : (single_post_title('', false) ?: nova_t('Actualités'));
get_template_part('template-parts/page-header', null, ['title' => $title, 'crumbs' => [[$title, null]]]);
?>
<section class="rv-wrap rv-section rv-section--top0"><div class="rv-prose rv-prose--page">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <article><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><?php the_excerpt(); ?></article>
    <?php endwhile; the_posts_pagination(); else : ?>
        <p><?php echo esc_html(nova_t('Aucun contenu.')); ?></p>
    <?php endif; ?>
</div></section>
<?php get_footer();
