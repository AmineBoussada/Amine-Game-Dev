<?php
/**
 * Top Solution > Forms: list and builder. All writes are POST + nonce +
 * ts_manage_forms, then redirect. A form is saved only if FormSchema accepts
 * it; otherwise nothing is stored and the reasons are listed.
 *
 * @package TopSolution
 */

namespace TopSolution\Admin;

use TopSolution\Core\Capabilities;
use TopSolution\Forms\DefaultForm;
use TopSolution\Forms\FieldTypes;
use TopSolution\Forms\FormRenderer;
use TopSolution\Forms\FormRepository;
use TopSolution\Forms\FormSchema;
use TopSolution\Forms\Visibility;

defined( 'ABSPATH' ) || exit;

/**
 * Forms admin.
 */
class FormsPage {

	const SLUG     = 'top-solution-forms';
	const NONCE    = 'ts_forms';
	const MAX_JSON = 204800; // 200 KB.

	/**
	 * Add the sub-menu and its POST handler.
	 *
	 * @param string $parent_slug Parent menu slug.
	 * @return void
	 */
	public static function register_menu( $parent_slug ) {
		$hook = add_submenu_page(
			$parent_slug,
			__( 'النماذج', 'top-solution-core' ),
			__( 'النماذج', 'top-solution-core' ),
			Capabilities::MANAGE_FORMS,
			self::SLUG,
			array( __CLASS__, 'render' )
		);
		if ( $hook ) {
			add_action( 'load-' . $hook, array( __CLASS__, 'handle_post' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		}
	}

	/**
	 * Per-user transient key.
	 *
	 * @param string $what Name.
	 * @return string
	 */
	private static function key( $what ) {
		return 'ts_forms_' . $what . '_' . get_current_user_id();
	}

	/**
	 * Queue a notice.
	 *
	 * @param string $type success | error | warning.
	 * @param string $text Message.
	 * @return void
	 */
	private static function notice( $type, $text ) {
		$notices   = get_transient( self::key( 'notice' ) );
		$notices   = is_array( $notices ) ? $notices : array();
		$notices[] = array( $type, $text );
		set_transient( self::key( 'notice' ), $notices, 5 * MINUTE_IN_SECONDS );
	}

	/**
	 * URL of the page.
	 *
	 * @param array $args Query args.
	 * @return string
	 */
	private static function url( array $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => self::SLUG ), $args ), admin_url( 'admin.php' ) );
	}

	/**
	 * Arabic text of a schema error.
	 *
	 * @param array $error code + field.
	 * @return string
	 */
	public static function error_text( array $error ) {
		$code  = $error['code'];
		$field = '' !== $error['field'] ? ' (' . $error['field'] . ')' : '';
		if ( 0 === strpos( $code, 'missing_mapping:' ) ) {
			$names = array(
				'customer_name'   => __( 'الاسم', 'top-solution-core' ),
				'phone'           => __( 'رقم الهاتف', 'top-solution-core' ),
				'wilaya'          => __( 'الولاية', 'top-solution-core' ),
				'delivery_method' => __( 'طريقة التوصيل', 'top-solution-core' ),
			);
			$map   = substr( $code, 16 );
			/* translators: %s: field name. */
			return sprintf( __( 'حقل «%s» إلزامي في كل نموذج طلب ولا يمكن حذفه.', 'top-solution-core' ), isset( $names[ $map ] ) ? $names[ $map ] : $map );
		}
		if ( 0 === strpos( $code, 'duplicate_mapping:' ) ) {
			return __( 'لا يمكن استخدام نفس الوظيفة في أكثر من حقل', 'top-solution-core' ) . $field;
		}
		$texts = array(
			'name_required'             => __( 'اسم النموذج مطلوب.', 'top-solution-core' ),
			'too_many_fields'           => __( 'عدد الحقول أكبر من المسموح (40).', 'top-solution-core' ),
			'duplicate_key'             => __( 'مفتاح الحقل مكرر', 'top-solution-core' ),
			'bad_key'                   => __( 'مفتاح الحقل غير صالح (حروف إنجليزية صغيرة وأرقام و _ ويبدأ بحرف)', 'top-solution-core' ),
			'bad_type'                  => __( 'نوع الحقل غير معروف', 'top-solution-core' ),
			'bad_mapping'               => __( 'الوظيفة المختارة لا تناسب هذا النوع', 'top-solution-core' ),
			'label_required'            => __( 'عنوان الحقل مطلوب', 'top-solution-core' ),
			'text_required'             => __( 'نص الحقل التوضيحي مطلوب', 'top-solution-core' ),
			'min_greater_than_max'      => __( 'الحد الأدنى أكبر من الحد الأقصى', 'top-solution-core' ),
			'options_required'          => __( 'أضف خياراً واحداً على الأقل', 'top-solution-core' ),
			'duplicate_option_value'    => __( 'قيمتان متطابقتان في الخيارات', 'top-solution-core' ),
			'bad_rule'                  => __( 'شرط الظهور غير صالح', 'top-solution-core' ),
			'rule_value_required'       => __( 'شرط الظهور يحتاج قيمة', 'top-solution-core' ),
			'conditional_without_rules' => __( 'اختر «حسب شرط» لكن لم تُضَف أي قاعدة', 'top-solution-core' ),
			'rule_unknown_field'        => __( 'شرط الظهور يشير إلى حقل غير موجود', 'top-solution-core' ),
			'rule_on_itself'            => __( 'لا يمكن ربط ظهور الحقل بنفسه', 'top-solution-core' ),
			'rule_on_info_field'        => __( 'لا يمكن ربط الظهور بنص توضيحي', 'top-solution-core' ),
			'rule_cycle'                => __( 'شروط الظهور تدور في حلقة (حقل يعتمد على حقل يعتمد عليه)', 'top-solution-core' ),
			'core_field_conditional'    => __( 'الحقول الأساسية (الاسم، الهاتف، الولاية، طريقة التوصيل) تظهر دائماً', 'top-solution-core' ),
		);
		return ( isset( $texts[ $code ] ) ? $texts[ $code ] : $code ) . $field;
	}

	/**
	 * Handle a POST, then redirect.
	 *
	 * @return void
	 */
	public static function handle_post() {
		if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Compared to a constant.
			return;
		}
		if ( ! current_user_can( Capabilities::MANAGE_FORMS ) ) {
			wp_die( esc_html__( 'ليست لديك صلاحية تعديل النماذج.', 'top-solution-core' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::NONCE );
		$action = isset( $_POST['ts_action'] ) ? sanitize_key( wp_unslash( $_POST['ts_action'] ) ) : '';
		$id     = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		$args   = array();

		switch ( $action ) {
			case 'create_form':
				$name = isset( $_POST['name'] ) ? \TopSolution\Core\Text::line( wp_unslash( $_POST['name'] ), 100 ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cleaned by Text::line().
				if ( '' === $name ) {
					self::notice( 'error', __( 'اكتب اسماً للنموذج الجديد.', 'top-solution-core' ) );
					break;
				}
				$definition         = FormSchema::normalize( DefaultForm::definition() )['definition'];
				$definition['name'] = $name;
				$new_id             = FormRepository::create( $definition );
				if ( $new_id ) {
					self::notice( 'success', __( 'تم إنشاء النموذج.', 'top-solution-core' ) );
					$args = array( 'form' => $new_id );
				}
				break;
			case 'duplicate_form':
				$new_id = FormRepository::duplicate( $id );
				self::notice( $new_id ? 'success' : 'error', $new_id ? __( 'تم نسخ النموذج.', 'top-solution-core' ) : __( 'تعذّر النسخ.', 'top-solution-core' ) );
				break;
			case 'default_form':
				self::notice( FormRepository::set_default( $id ) ? 'success' : 'error', __( 'تم تعيين النموذج الافتراضي.', 'top-solution-core' ) );
				break;
			case 'delete_form':
				self::notice( FormRepository::delete( $id ) ? 'success' : 'error', FormRepository::delete( $id ) || ! FormRepository::get( $id ) ? __( 'تم حذف النموذج.', 'top-solution-core' ) : __( 'لا يمكن حذف النموذج الافتراضي. عيّن نموذجاً آخر افتراضياً أولاً.', 'top-solution-core' ) );
				break;
			case 'save_form':
				$args = self::save_form( $id );
				break;
		}
		wp_safe_redirect( self::url( $args ) );
		exit;
	}

	/**
	 * Save a form from the builder.
	 *
	 * @param int $id Form id.
	 * @return array Redirect query args.
	 */
	private static function save_form( $id ) {
		$args = array( 'form' => $id );
		if ( ! FormRepository::get( $id ) ) {
			self::notice( 'error', __( 'النموذج غير موجود.', 'top-solution-core' ) );
			return array();
		}
		$json = isset( $_POST['fields_json'] ) ? (string) wp_unslash( $_POST['fields_json'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- Decoded and fully validated by FormSchema; nonce checked in handle_post().
		if ( strlen( $json ) > self::MAX_JSON ) {
			self::notice( 'error', __( 'بيانات النموذج كبيرة جداً.', 'top-solution-core' ) );
			return $args;
		}
		$fields = json_decode( $json, true );
		$input  = array(
			'name'     => isset( $_POST['name'] ) ? wp_unslash( $_POST['name'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- Cleaned by FormSchema.
			'settings' => array( 'submit_label' => isset( $_POST['submit_label'] ) ? wp_unslash( $_POST['submit_label'] ) : '' ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- Cleaned by FormSchema.
			'fields'   => is_array( $fields ) ? $fields : array(),
		);
		$result = FormSchema::normalize( $input );
		if ( ! is_array( $fields ) ) {
			$result['errors'][] = array(
				'code'  => 'bad_json',
				'field' => '',
			);
		}
		if ( $result['errors'] ) {
			// Nothing is saved; keep what the person typed so they can fix it.
			set_transient(
				self::key( 'draft' ),
				array(
					'id'    => $id,
					'input' => $input,
				),
				15 * MINUTE_IN_SECONDS
			);
			$lines = array_map( array( __CLASS__, 'error_text' ), $result['errors'] );
			self::notice( 'error', __( 'لم يُحفظ النموذج. صحّح ما يلي:', 'top-solution-core' ) . ' ' . implode( ' · ', array_slice( $lines, 0, 8 ) ) );
			return $args;
		}
		delete_transient( self::key( 'draft' ) );
		FormRepository::update( $id, $result['definition'] );
		self::notice( 'success', __( 'تم حفظ النموذج.', 'top-solution-core' ) );
		return $args;
	}

	/**
	 * Builder assets and data.
	 *
	 * @param string $hook_suffix Page hook.
	 * @return void
	 */
	public static function enqueue( $hook_suffix ) {
		if ( false === strpos( (string) $hook_suffix, self::SLUG ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page selection.
		$form_id = isset( $_GET['form'] ) ? absint( $_GET['form'] ) : 0;
		if ( ! $form_id ) {
			return;
		}
		wp_enqueue_script( 'ts-core-builder', TS_CORE_URL . 'assets/builder.js', array(), TS_CORE_VERSION, array( 'in_footer' => true ) );
		$form  = FormRepository::get( $form_id );
		$draft = get_transient( self::key( 'draft' ) );
		$state = $form ? $form['fields'] : array();
		if ( is_array( $draft ) && (int) $draft['id'] === $form_id ) {
			$state = isset( $draft['input']['fields'] ) ? $draft['input']['fields'] : $state;
		}
		$types = array();
		foreach ( FieldTypes::all() as $id => $spec ) {
			$types[ $id ] = array(
				'label'    => FieldTypes::label( $id ),
				'input'    => $spec['input'],
				'options'  => $spec['options'],
				'mappings' => $spec['mappings'],
				'default'  => $spec['default_mapping'],
			);
		}
		$data = array(
			'fields'   => $state,
			'types'    => $types,
			'addable'  => FieldTypes::addable(),
			'core'     => FieldTypes::required_mappings(),
			'mappings' => array(
				'customer_name'   => __( 'اسم العميل', 'top-solution-core' ),
				'phone'           => __( 'هاتف العميل', 'top-solution-core' ),
				'wilaya'          => __( 'الولاية', 'top-solution-core' ),
				'commune'         => __( 'البلدية', 'top-solution-core' ),
				'address'         => __( 'العنوان', 'top-solution-core' ),
				'note'            => __( 'ملاحظة الطلب', 'top-solution-core' ),
				'delivery_method' => __( 'طريقة التوصيل', 'top-solution-core' ),
				'pickup_point'    => __( 'مكتب الاستلام', 'top-solution-core' ),
				'quantity'        => __( 'الكمية', 'top-solution-core' ),
				'meta'            => __( 'يُحفظ مع الطلب كمعلومة إضافية', 'top-solution-core' ),
			),
			'ops'      => array(
				'eq'        => __( 'يساوي', 'top-solution-core' ),
				'neq'       => __( 'لا يساوي', 'top-solution-core' ),
				'in'        => __( 'ضمن القائمة (مفصولة بفاصلة)', 'top-solution-core' ),
				'empty'     => __( 'فارغ', 'top-solution-core' ),
				'not_empty' => __( 'غير فارغ', 'top-solution-core' ),
			),
			'i18n'     => array(
				'up'               => __( 'تحريك للأعلى', 'top-solution-core' ),
				'down'             => __( 'تحريك للأسفل', 'top-solution-core' ),
				'edit'             => __( 'تعديل', 'top-solution-core' ),
				'close'            => __( 'إغلاق', 'top-solution-core' ),
				'remove'           => __( 'حذف', 'top-solution-core' ),
				'locked'           => __( 'حقل أساسي: لا يُحذف ويبقى إلزامياً', 'top-solution-core' ),
				'label'            => __( 'عنوان الحقل', 'top-solution-core' ),
				'key'              => __( 'مفتاح الحقل (إنجليزي)', 'top-solution-core' ),
				'keyHelp'          => __( 'يستخدمه النظام داخلياً. غيّره بحذر: شروط الظهور تشير إليه.', 'top-solution-core' ),
				'placeholder'      => __( 'نص إرشادي داخل الحقل', 'top-solution-core' ),
				'help'             => __( 'نص مساعد تحت الحقل', 'top-solution-core' ),
				'width'            => __( 'العرض', 'top-solution-core' ),
				'full'             => __( 'كامل', 'top-solution-core' ),
				'half'             => __( 'نصف', 'top-solution-core' ),
				'required'         => __( 'إلزامي', 'top-solution-core' ),
				'maps'             => __( 'الوظيفة في الطلب', 'top-solution-core' ),
				'min'              => __( 'الحد الأدنى', 'top-solution-core' ),
				'max'              => __( 'الحد الأقصى', 'top-solution-core' ),
				'options'          => __( 'الخيارات: سطر لكل خيار. الصيغة value|العنوان أو العنوان فقط', 'top-solution-core' ),
				'text'             => __( 'النص التوضيحي', 'top-solution-core' ),
				'visibility'       => __( 'الظهور', 'top-solution-core' ),
				'always'           => __( 'دائماً', 'top-solution-core' ),
				'conditional'      => __( 'حسب شرط', 'top-solution-core' ),
				'showIf'           => __( 'أظهر الحقل إذا', 'top-solution-core' ),
				'addRule'          => __( 'إضافة شرط', 'top-solution-core' ),
				'removeRule'       => __( 'حذف الشرط', 'top-solution-core' ),
				'value'            => __( 'القيمة', 'top-solution-core' ),
				'field'            => __( 'الحقل', 'top-solution-core' ),
				'operator'         => __( 'العملية', 'top-solution-core' ),
				'conditionalBadge' => __( 'مشروط', 'top-solution-core' ),
				'requiredBadge'    => __( 'إلزامي', 'top-solution-core' ),
				'untitled'         => __( '(بلا عنوان)', 'top-solution-core' ),
				'allRules'         => __( 'كل الشروط يجب أن تتحقق معاً', 'top-solution-core' ),
				'moved'            => __( 'تم نقل الحقل', 'top-solution-core' ),
				'added'            => __( 'تمت إضافة الحقل', 'top-solution-core' ),
				'removed'          => __( 'تم حذف الحقل', 'top-solution-core' ),
			),
		);
		wp_add_inline_script( 'ts-core-builder', 'window.tsBuilder = ' . wp_json_encode( $data ) . ';', 'before' );
	}

	/**
	 * Hidden nonce + action fields.
	 *
	 * @param string $action Action id.
	 * @return void
	 */
	private static function form_fields( $action ) {
		wp_nonce_field( self::NONCE );
		echo '<input type="hidden" name="ts_action" value="' . esc_attr( $action ) . '">';
	}

	/**
	 * Render the page.
	 *
	 * @return void
	 */
	public static function render() {
		if ( ! current_user_can( Capabilities::MANAGE_FORMS ) ) {
			wp_die( esc_html__( 'ليست لديك صلاحية عرض هذه الصفحة.', 'top-solution-core' ), '', array( 'response' => 403 ) );
		}
		echo '<div class="wrap ts-admin"><h1>' . esc_html__( 'Top Solution — النماذج', 'top-solution-core' ) . '</h1>';
		$notices = get_transient( self::key( 'notice' ) );
		delete_transient( self::key( 'notice' ) );
		foreach ( is_array( $notices ) ? $notices : array() as $notice ) {
			echo '<div class="notice notice-' . esc_attr( $notice[0] ) . ' is-dismissible"><p>' . esc_html( $notice[1] ) . '</p></div>';
		}
		$form_id = isset( $_GET['form'] ) ? absint( $_GET['form'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page selection.
		if ( $form_id && FormRepository::get( $form_id ) ) {
			self::render_builder( $form_id );
		} else {
			self::render_list();
		}
		echo '</div>';
	}

	/**
	 * Forms list.
	 *
	 * @return void
	 */
	private static function render_list() {
		$forms   = FormRepository::all();
		$default = FormRepository::default_id();
		?>
		<p class="ts-lead"><?php esc_html_e( 'النماذج تُستخدم في صفحات الطلب. النموذج الافتراضي يُستخدم عند عدم اختيار غيره. الحقول الأساسية (الاسم، الهاتف، الولاية، طريقة التوصيل) موجودة في كل نموذج.', 'top-solution-core' ); ?></p>
		<div class="ts-scroll">
		<table class="widefat striped">
			<thead><tr>
				<th scope="col"><?php esc_html_e( 'النموذج', 'top-solution-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'الحقول', 'top-solution-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'الحالة', 'top-solution-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'إجراءات', 'top-solution-core' ); ?></th>
			</tr></thead>
			<tbody>
			<?php foreach ( $forms as $f ) : ?>
				<tr>
					<th scope="row"><a href="<?php echo esc_url( self::url( array( 'form' => $f['id'] ) ) ); ?>"><?php echo esc_html( $f['name'] ); ?></a></th>
					<td><?php echo esc_html( (string) $f['fields'] ); ?></td>
					<td><?php echo $f['id'] === $default ? '<span class="ts-badge ts-badge--ok">✓ ' . esc_html__( 'افتراضي', 'top-solution-core' ) . '</span>' : ''; ?></td>
					<td class="ts-row-actions">
						<a class="button button-small" href="<?php echo esc_url( self::url( array( 'form' => $f['id'] ) ) ); ?>"><?php esc_html_e( 'تعديل', 'top-solution-core' ); ?></a>
						<form method="post" class="ts-inline"><?php self::form_fields( 'duplicate_form' ); ?><input type="hidden" name="form_id" value="<?php echo esc_attr( (string) $f['id'] ); ?>"><button type="submit" class="button button-small"><?php esc_html_e( 'نسخ', 'top-solution-core' ); ?></button></form>
						<?php if ( $f['id'] !== $default ) : ?>
							<form method="post" class="ts-inline"><?php self::form_fields( 'default_form' ); ?><input type="hidden" name="form_id" value="<?php echo esc_attr( (string) $f['id'] ); ?>"><button type="submit" class="button button-small"><?php esc_html_e( 'تعيين كافتراضي', 'top-solution-core' ); ?></button></form>
							<form method="post" class="ts-inline"><?php self::form_fields( 'delete_form' ); ?><input type="hidden" name="form_id" value="<?php echo esc_attr( (string) $f['id'] ); ?>"><button type="submit" class="button button-small button-link-delete"><?php esc_html_e( 'حذف', 'top-solution-core' ); ?></button></form>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		</div>
		<h2><?php esc_html_e( 'نموذج جديد', 'top-solution-core' ); ?></h2>
		<form method="post" class="ts-inline-form">
			<?php self::form_fields( 'create_form' ); ?>
			<label for="ts-new-name" class="screen-reader-text"><?php esc_html_e( 'اسم النموذج', 'top-solution-core' ); ?></label>
			<input type="text" id="ts-new-name" name="name" class="regular-text" placeholder="<?php esc_attr_e( 'اسم النموذج', 'top-solution-core' ); ?>" required>
			<?php submit_button( __( 'إنشاء', 'top-solution-core' ), 'secondary', 'submit', false ); ?>
			<span class="description"><?php esc_html_e( 'يبدأ بنسخة من الحقول الأساسية.', 'top-solution-core' ); ?></span>
		</form>
		<?php
	}

	/**
	 * Builder.
	 *
	 * @param int $form_id Form id.
	 * @return void
	 */
	private static function render_builder( $form_id ) {
		$form  = FormRepository::get( $form_id );
		$draft = get_transient( self::key( 'draft' ) );
		$name  = $form['name'];
		$label = $form['settings']['submit_label'];
		if ( is_array( $draft ) && (int) $draft['id'] === $form_id ) {
			$name  = isset( $draft['input']['name'] ) ? \TopSolution\Core\Text::line( $draft['input']['name'], 100 ) : $name;
			$label = isset( $draft['input']['settings']['submit_label'] ) ? \TopSolution\Core\Text::line( $draft['input']['settings']['submit_label'], 60 ) : $label;
		}
		?>
		<p><a href="<?php echo esc_url( self::url() ); ?>">&larr; <?php esc_html_e( 'كل النماذج', 'top-solution-core' ); ?></a></p>
		<form method="post" class="ts-builder-form" id="ts-builder-form">
			<?php self::form_fields( 'save_form' ); ?>
			<input type="hidden" name="form_id" value="<?php echo esc_attr( (string) $form_id ); ?>">
			<input type="hidden" name="fields_json" id="ts-fields-json" value="">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="ts-form-name"><?php esc_html_e( 'اسم النموذج', 'top-solution-core' ); ?></label></th>
					<td><input type="text" id="ts-form-name" name="name" class="regular-text" value="<?php echo esc_attr( $name ); ?>" required></td>
				</tr>
				<tr>
					<th scope="row"><label for="ts-submit-label"><?php esc_html_e( 'نص زر الإرسال', 'top-solution-core' ); ?></label></th>
					<td><input type="text" id="ts-submit-label" name="submit_label" class="regular-text" value="<?php echo esc_attr( $label ); ?>"></td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'الحقول', 'top-solution-core' ); ?></h2>
			<p class="description"><?php esc_html_e( 'رتّب الحقول بأزرار الأسهم. الحقول الأساسية لا تُحذف. تحقق الخادم من كل نموذج عند الحفظ ولن يُحفظ نموذج غير صالح.', 'top-solution-core' ); ?></p>
			<div id="ts-builder" class="ts-builder" aria-label="<?php esc_attr_e( 'حقول النموذج', 'top-solution-core' ); ?>"></div>
			<p class="ts-builder-add">
				<label for="ts-add-type"><?php esc_html_e( 'إضافة حقل:', 'top-solution-core' ); ?></label>
				<select id="ts-add-type">
					<?php foreach ( FieldTypes::addable() as $type ) : ?>
						<option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( FieldTypes::label( $type ) ); ?></option>
					<?php endforeach; ?>
				</select>
				<button type="button" class="button" id="ts-add-field"><?php esc_html_e( 'إضافة', 'top-solution-core' ); ?></button>
			</p>
			<p class="screen-reader-text" id="ts-builder-status" role="status" aria-live="polite"></p>
			<?php submit_button( __( 'حفظ النموذج', 'top-solution-core' ) ); ?>
		</form>

		<h2><?php esc_html_e( 'معاينة (آخر نسخة محفوظة)', 'top-solution-core' ); ?></h2>
		<p class="description"><?php esc_html_e( 'تعمل الشروط والقوائم المرتبطة كما ستعمل في الصفحة. لا يُرسَل أي طلب من المعاينة.', 'top-solution-core' ); ?></p>
		<div class="ts-card ts-form-preview"><?php echo FormRenderer::render( $form, array( 'preview' => true ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside FormRenderer. ?></div>
		<?php
	}
}
