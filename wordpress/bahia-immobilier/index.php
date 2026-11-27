<?php
/** Fallback template (blog posts, search results). */
defined('ABSPATH') || exit;
get_header();
get_template_part('template-parts/page-header', null, ['title' => is_search() ? bahia_t('Résultats de recherche') : (single_post_title('', false) ?: bahia_t('Actualités')), 'crumbs' => []]);
?>
<section class="section"><div class="container prose">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <article><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><?php the_excerpt(); ?></article>
    <?php endwhile; the_posts_pagination(); else : ?>
        <p><?php echo esc_html(bahia_t('Aucun contenu.')); ?></p>
    <?php endif; ?>
</div></section>
<?php get_footer();
