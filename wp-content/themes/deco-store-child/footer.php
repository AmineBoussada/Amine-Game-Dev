<?php
/**
 * Site footer and closing tags.
 *
 * @package DecoStoreChild
 */

defined( 'ABSPATH' ) || exit;

if ( function_exists( 'hello_elementor_display_header_footer' ) ? hello_elementor_display_header_footer() : true ) {
	get_template_part( 'template-parts/site-footer' );
}

wp_footer();
?>
</body>
</html>
