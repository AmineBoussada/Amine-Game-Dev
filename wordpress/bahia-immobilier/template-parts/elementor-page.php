<?php
/** A page built with Elementor, inside the theme header and footer (language and light/dark buttons kept). */
defined('ABSPATH') || exit;
get_header();
while (have_posts()) :
    the_post();
    echo '<div class="bahia-elementor-content">';
    the_content();
    echo '</div>';
endwhile;
get_footer();
