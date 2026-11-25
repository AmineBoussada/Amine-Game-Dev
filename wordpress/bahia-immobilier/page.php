<?php
defined('ABSPATH') || exit;
get_header();
the_post();
get_template_part('template-parts/page-header', null, ['title' => get_the_title(), 'crumbs' => [[get_the_title(), null]]]);
?>
<section class="rv-wrap rv-section rv-section--top0"><div class="rv-prose rv-prose--page"<?php echo bahia_lang_attr('fr'); ?>><?php the_content(); ?></div></section>
<?php get_footer();
