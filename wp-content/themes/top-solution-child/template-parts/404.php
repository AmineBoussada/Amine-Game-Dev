<?php
/**
 * 404 layout.
 *
 * @package TopSolutionChild
 */

defined( 'ABSPATH' ) || exit;
?>
<main id="content" class="ts-main ts-page ts-page--404">
	<div class="ts-container">
		<div class="ts-notfound">
			<p class="ts-notfound__code" aria-hidden="true">404</p>
			<h1 class="ts-page__title"><?php esc_html_e( 'الصفحة غير موجودة', 'top-solution-child' ); ?></h1>
			<p><?php esc_html_e( 'ربما تم نقل الرابط أو حذفه. يمكنك البحث عن ما تريد أو العودة إلى الصفحة الرئيسية.', 'top-solution-child' ); ?></p>
			<div class="ts-notfound__search"><?php get_search_form(); ?></div>
			<a class="ts-btn" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'العودة إلى الرئيسية', 'top-solution-child' ); ?></a>
		</div>
	</div>
</main>
