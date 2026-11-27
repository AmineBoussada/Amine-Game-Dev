<?php
defined('ABSPATH') || exit;
if (nova_render_elementor_page()) {
    return; // page built with Elementor
}
get_header();
the_post();
get_template_part('template-parts/page-header', null, ['title' => get_the_title(), 'crumbs' => [[get_the_title(), null]]]);
?>
<section class="section"><div class="container prose"<?php echo nova_lang_attr('fr'); ?>><?php the_content(); ?></div></section>
<?php get_footer();
