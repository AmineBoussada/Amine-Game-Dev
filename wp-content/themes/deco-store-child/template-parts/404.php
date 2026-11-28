<?php
/**
 * 404 layout.
 *
 * @package DecoStoreChild
 */

defined( 'ABSPATH' ) || exit;
?>
<main id="content" class="ds-main ds-page ds-page--404">
	<div class="ds-container">
		<div class="ds-notfound">
			<p class="ds-notfound__code" aria-hidden="true">404</p>
			<h1 class="ds-page__title"><?php esc_html_e( 'الصفحة غير موجودة', 'deco-store-child' ); ?></h1>
			<p><?php esc_html_e( 'ربما تم نقل الرابط أو حذفه. يمكنك البحث عن ما تريد أو العودة إلى الصفحة الرئيسية.', 'deco-store-child' ); ?></p>
			<div class="ds-notfound__search"><?php get_search_form(); ?></div>
			<a class="ds-btn" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'العودة إلى الرئيسية', 'deco-store-child' ); ?></a>
		</div>
	</div>
</main>
