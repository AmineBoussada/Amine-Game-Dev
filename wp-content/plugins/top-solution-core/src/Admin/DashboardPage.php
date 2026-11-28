<?php
/**
 * Dashboard: system status and setup checklist.
 *
 * @package TopSolution
 */

namespace TopSolution\Admin;

use TopSolution\Core\Capabilities;
use TopSolution\Core\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the status page.
 */
class DashboardPage {

	/**
	 * Render the page.
	 *
	 * @return void
	 */
	public static function render() {
		if ( ! current_user_can( Capabilities::VIEW_REPORTS ) ) {
			wp_die( esc_html__( 'ليست لديك صلاحية عرض هذه الصفحة.', 'top-solution-core' ), '', array( 'response' => 403 ) );
		}
		$checks = StatusChecks::run();
		$labels = array(
			StatusChecks::OK   => '✓ ' . __( 'سليم', 'top-solution-core' ),
			StatusChecks::WARN => '! ' . __( 'تنبيه', 'top-solution-core' ),
			StatusChecks::FAIL => '✕ ' . __( 'مشكلة', 'top-solution-core' ),
		);
		$status = Settings::get( 'initial_order_status' );
		$fails  = count(
			array_filter(
				$checks,
				static function ( $c ) {
					return StatusChecks::FAIL === $c['status'];
				}
			)
		);
		$warns  = count(
			array_filter(
				$checks,
				static function ( $c ) {
					return StatusChecks::WARN === $c['status'];
				}
			)
		);
		?>
		<div class="wrap ts-admin">
			<h1><?php esc_html_e( 'Top Solution — لوحة التحكم', 'top-solution-core' ); ?></h1>
			<p class="ts-lead"><?php esc_html_e( 'حالة النظام وقائمة الإعداد. لا تغيّر هذه الصفحة أي بيانات.', 'top-solution-core' ); ?></p>

			<div class="ts-summary">
				<span class="ts-pill ts-pill--<?php echo $fails ? 'fail' : 'ok'; ?>">
					<?php
					echo $fails
						/* translators: %d: number of problems. */
						? esc_html( sprintf( _n( '%d مشكلة', '%d مشاكل', $fails, 'top-solution-core' ), $fails ) )
						: esc_html__( 'لا توجد مشاكل', 'top-solution-core' );
					?>
				</span>
				<span class="ts-pill ts-pill--<?php echo $warns ? 'warn' : 'ok'; ?>">
					<?php
					echo $warns
						/* translators: %d: number of warnings. */
						? esc_html( sprintf( _n( '%d تنبيه', '%d تنبيهات', $warns, 'top-solution-core' ), $warns ) )
						: esc_html__( 'لا توجد تنبيهات', 'top-solution-core' );
					?>
				</span>
				<span class="ts-pill">
					<?php
					/* translators: %s: order status label. */
					echo esc_html( sprintf( __( 'حالة الطلب الجديد: %s', 'top-solution-core' ), 'processing' === $status ? __( 'قيد المعالجة', 'top-solution-core' ) : __( 'معلّق', 'top-solution-core' ) ) );
					?>
				</span>
			</div>

			<table class="widefat striped ts-status" role="table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'البند', 'top-solution-core' ); ?></th>
						<th scope="col"><?php esc_html_e( 'الحالة', 'top-solution-core' ); ?></th>
						<th scope="col"><?php esc_html_e( 'التفاصيل', 'top-solution-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $checks as $check ) : ?>
					<tr>
						<th scope="row"><?php echo esc_html( $check['label'] ); ?></th>
						<td><span class="ts-badge ts-badge--<?php echo esc_attr( $check['status'] ); ?>"><?php echo esc_html( $labels[ $check['status'] ] ); ?></span></td>
						<td><?php echo esc_html( $check['message'] ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
