<?php
/**
 * Déco Store > Forms: list and builder. All writes are POST + nonce +
 * ds_manage_forms, then redirect. A form is saved only if FormSchema accepts
 * it; otherwise nothing is stored and the reasons are listed.
 *
 * @package DecoStore
 */

namespace DecoStore\Admin;

use DecoStore\Core\Capabilities;
use DecoStore\Forms\DefaultForm;
use DecoStore\Forms\FieldTypes;
use DecoStore\Forms\FormRenderer;
use DecoStore\Forms\FormRepository;
use DecoStore\Forms\FormSchema;
use DecoStore\Forms\Visibility;

defined( 'ABSPATH' ) || exit;

/**
 * Forms admin.
 */
class FormsPage {

	const SLUG     = 'deco-store-forms';
	const NONCE    = 'ds_forms';
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
			__( 'النماذج', 'deco-store-core' ),
			__( 'النماذج', 'deco-store-core' ),
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
		return 'ds_forms_' . $what . '_' . get_current_user_id();
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
				'customer_name'   => __( 'الاسم', 'deco-store-core' ),
				'phone'           => __( 'رقم الهاتف', 'deco-store-core' ),
				'wilaya'          => __( 'الولاية', 'deco-store-core' ),
				'delivery_method' => __( 'طريقة التوصيل', 'deco-store-core' ),
			);
			$map   = substr( $code, 16 );
			/* translators: %s: field name. */
			return sprintf( __( 'حقل «%s» إلزامي في كل نموذج طلب ولا يمكن حذفه.', 'deco-store-core' ), isset( $names[ $map ] ) ? $names[ $map ] : $map );
		}
		if ( 0 === strpos( $code, 'duplicate_mapping:' ) ) {
			return __( 'لا يمكن استخدام نفس الوظيفة في أكثر من حقل', 'deco-store-core' ) . $field;
		}
		$texts = array(
			'name_required'             => __( 'اسم النموذج مطلوب.', 'deco-store-core' ),
			'too_many_fields'           => __( 'عدد الحقول أكبر من المسموح (40).', 'deco-store-core' ),
			'duplicate_key'             => __( 'مفتاح الحقل مكرر', 'deco-store-core' ),
			'bad_key'                   => __( 'مفتاح الحقل غير صالح (حروف إنجليزية صغيرة وأرقام و _ ويبدأ بحرف)', 'deco-store-core' ),
			'bad_type'                  => __( 'نوع الحقل غير معروف', 'deco-store-core' ),
			'bad_mapping'               => __( 'الوظيفة المختارة لا تناسب هذا النوع', 'deco-store-core' ),
			'label_required'            => __( 'عنوان الحقل مطلوب', 'deco-store-core' ),
			'text_required'             => __( 'نص الحقل التوضيحي مطلوب', 'deco-store-core' ),
			'min_greater_than_max'      => __( 'الحد الأدنى أكبر من الحد الأقصى', 'deco-store-core' ),
			'options_required'          => __( 'أضف خياراً واحداً على الأقل', 'deco-store-core' ),
			'duplicate_option_value'    => __( 'قيمتان متطابقتان في الخيارات', 'deco-store-core' ),
			'bad_rule'                  => __( 'شرط الظهور غير صالح', 'deco-store-core' ),
			'rule_value_required'       => __( 'شرط الظهور يحتاج قيمة', 'deco-store-core' ),
			'conditional_without_rules' => __( 'اختر «حسب شرط» لكن لم تُضَف أي قاعدة', 'deco-store-core' ),
			'rule_unknown_field'        => __( 'شرط الظهور يشير إلى حقل غير موجود', 'deco-store-core' ),
			'rule_on_itself'            => __( 'لا يمكن ربط ظهور الحقل بنفسه', 'deco-store-core' ),
			'rule_on_info_field'        => __( 'لا يمكن ربط الظهور بنص توضيحي', 'deco-store-core' ),
			'rule_cycle'                => __( 'شروط الظهور تدور في حلقة (حقل يعتمد على حقل يعتمد عليه)', 'deco-store-core' ),
			'core_field_conditional'    => __( 'الحقول الأساسية (الاسم، الهاتف، الولاية، طريقة التوصيل) تظهر دائماً', 'deco-store-core' ),
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
			wp_die( esc_html__( 'ليست لديك صلاحية تعديل النماذج.', 'deco-store-core' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::NONCE );
		$action = isset( $_POST['ds_action'] ) ? sanitize_key( wp_unslash( $_POST['ds_action'] ) ) : '';
		$id     = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		$args   = array();

		switch ( $action ) {
			case 'create_form':
				$name = isset( $_POST['name'] ) ? \DecoStore\Core\Text::line( wp_unslash( $_POST['name'] ), 100 ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cleaned by Text::line().
				if ( '' === $name ) {
					self::notice( 'error', __( 'اكتب اسماً للنموذج الجديد.', 'deco-store-core' ) );
					break;
				}
				$definition         = FormSchema::normalize( DefaultForm::definition() )['definition'];
				$definition['name'] = $name;
				$new_id             = FormRepository::create( $definition );
				if ( $new_id ) {
					self::notice( 'success', __( 'تم إنشاء النموذج.', 'deco-store-core' ) );
					$args = array( 'form' => $new_id );
				}
				break;
			case 'duplicate_form':
				$new_id = FormRepository::duplicate( $id );
				self::notice( $new_id ? 'success' : 'error', $new_id ? __( 'تم نسخ النموذج.', 'deco-store-core' ) : __( 'تعذّر النسخ.', 'deco-store-core' ) );
				break;
			case 'default_form':
				self::notice( FormRepository::set_default( $id ) ? 'success' : 'error', __( 'تم تعيين النموذج الافتراضي.', 'deco-store-core' ) );
				break;
			case 'delete_form':
				self::notice( FormRepository::delete( $id ) ? 'success' : 'error', FormRepository::delete( $id ) || ! FormRepository::get( $id ) ? __( 'تم حذف النموذج.', 'deco-store-core' ) : __( 'لا يمكن حذف النموذج الافتراضي. عيّن نموذجاً آخر افتراضياً أولاً.', 'deco-store-core' ) );
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
			self::notice( 'error', __( 'النموذج غير موجود.', 'deco-store-core' ) );
			return array();
		}
		$json = isset( $_POST['fields_json'] ) ? (string) wp_unslash( $_POST['fields_json'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- Decoded and fully validated by FormSchema; nonce checked in handle_post().
		if ( strlen( $json ) > self::MAX_JSON ) {
			self::notice( 'error', __( 'بيانات النموذج كبيرة جداً.', 'deco-store-core' ) );
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
			self::notice( 'error', __( 'لم يُحفظ النموذج. صحّح ما يلي:', 'deco-store-core' ) . ' ' . implode( ' · ', array_slice( $lines, 0, 8 ) ) );
			return $args;
		}
		delete_transient( self::key( 'draft' ) );
		FormRepository::update( $id, $result['definition'] );
		self::notice( 'success', __( 'تم حفظ النموذج.', 'deco-store-core' ) );
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
		wp_enqueue_script( 'ds-core-builder', DS_CORE_URL . 'assets/builder.js', array(), DS_CORE_VERSION, array( 'in_footer' => true ) );
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
				'customer_name'   => __( 'اسم العميل', 'deco-store-core' ),
				'phone'           => __( 'هاتف العميل', 'deco-store-core' ),
				'wilaya'          => __( 'الولاية', 'deco-store-core' ),
				'commune'         => __( 'البلدية', 'deco-store-core' ),
				'address'         => __( 'العنوان', 'deco-store-core' ),
				'note'            => __( 'ملاحظة الطلب', 'deco-store-core' ),
				'delivery_method' => __( 'طريقة التوصيل', 'deco-store-core' ),
				'pickup_point'    => __( 'مكتب الاستلام', 'deco-store-core' ),
				'quantity'        => __( 'الكمية', 'deco-store-core' ),
				'meta'            => __( 'يُحفظ مع الطلب كمعلومة إضافية', 'deco-store-core' ),
			),
			'ops'      => array(
				'eq'        => __( 'يساوي', 'deco-store-core' ),
				'neq'       => __( 'لا يساوي', 'deco-store-core' ),
				'in'        => __( 'ضمن القائمة (مفصولة بفاصلة)', 'deco-store-core' ),
				'empty'     => __( 'فارغ', 'deco-store-core' ),
				'not_empty' => __( 'غير فارغ', 'deco-store-core' ),
			),
			'i18n'     => array(
				'up'               => __( 'تحريك للأعلى', 'deco-store-core' ),
				'down'             => __( 'تحريك للأسفل', 'deco-store-core' ),
				'edit'             => __( 'تعديل', 'deco-store-core' ),
				'close'            => __( 'إغلاق', 'deco-store-core' ),
				'remove'           => __( 'حذف', 'deco-store-core' ),
				'locked'           => __( 'حقل أساسي: لا يُحذف ويبقى إلزامياً', 'deco-store-core' ),
				'label'            => __( 'عنوان الحقل', 'deco-store-core' ),
				'key'              => __( 'مفتاح الحقل (إنجليزي)', 'deco-store-core' ),
				'keyHelp'          => __( 'يستخدمه النظام داخلياً. غيّره بحذر: شروط الظهور تشير إليه.', 'deco-store-core' ),
				'placeholder'      => __( 'نص إرشادي داخل الحقل', 'deco-store-core' ),
				'help'             => __( 'نص مساعد تحت الحقل', 'deco-store-core' ),
				'width'            => __( 'العرض', 'deco-store-core' ),
				'full'             => __( 'كامل', 'deco-store-core' ),
				'half'             => __( 'نصف', 'deco-store-core' ),
				'required'         => __( 'إلزامي', 'deco-store-core' ),
				'maps'             => __( 'الوظيفة في الطلب', 'deco-store-core' ),
				'min'              => __( 'الحد الأدنى', 'deco-store-core' ),
				'max'              => __( 'الحد الأقصى', 'deco-store-core' ),
				'options'          => __( 'الخيارات: سطر لكل خيار. الصيغة value|العنوان أو العنوان فقط', 'deco-store-core' ),
				'text'             => __( 'النص التوضيحي', 'deco-store-core' ),
				'visibility'       => __( 'الظهور', 'deco-store-core' ),
				'always'           => __( 'دائماً', 'deco-store-core' ),
				'conditional'      => __( 'حسب شرط', 'deco-store-core' ),
				'showIf'           => __( 'أظهر الحقل إذا', 'deco-store-core' ),
				'addRule'          => __( 'إضافة شرط', 'deco-store-core' ),
				'removeRule'       => __( 'حذف الشرط', 'deco-store-core' ),
				'value'            => __( 'القيمة', 'deco-store-core' ),
				'field'            => __( 'الحقل', 'deco-store-core' ),
				'operator'         => __( 'العملية', 'deco-store-core' ),
				'conditionalBadge' => __( 'مشروط', 'deco-store-core' ),
				'requiredBadge'    => __( 'إلزامي', 'deco-store-core' ),
				'untitled'         => __( '(بلا عنوان)', 'deco-store-core' ),
				'allRules'         => __( 'كل الشروط يجب أن تتحقق معاً', 'deco-store-core' ),
				'moved'            => __( 'تم نقل الحقل', 'deco-store-core' ),
				'added'            => __( 'تمت إضافة الحقل', 'deco-store-core' ),
				'removed'          => __( 'تم حذف الحقل', 'deco-store-core' ),
			),
		);
		wp_add_inline_script( 'ds-core-builder', 'window.dsBuilder = ' . wp_json_encode( $data ) . ';', 'before' );
	}

	/**
	 * Hidden nonce + action fields.
	 *
	 * @param string $action Action id.
	 * @return void
	 */
	private static function form_fields( $action ) {
		wp_nonce_field( self::NONCE );
		echo '<input type="hidden" name="ds_action" value="' . esc_attr( $action ) . '">';
	}

	/**
	 * Render the page.
	 *
	 * @return void
	 */
	public static function render() {
		if ( ! current_user_can( Capabilities::MANAGE_FORMS ) ) {
			wp_die( esc_html__( 'ليست لديك صلاحية عرض هذه الصفحة.', 'deco-store-core' ), '', array( 'response' => 403 ) );
		}
		echo '<div class="wrap ds-admin"><h1>' . esc_html__( 'Déco Store — النماذج', 'deco-store-core' ) . '</h1>';
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
		<p class="ds-lead"><?php esc_html_e( 'النماذج تُستخدم في صفحات الطلب. النموذج الافتراضي يُستخدم عند عدم اختيار غيره. الحقول الأساسية (الاسم، الهاتف، الولاية، طريقة التوصيل) موجودة في كل نموذج.', 'deco-store-core' ); ?></p>
		<div class="ds-scroll">
		<table class="widefat striped">
			<thead><tr>
				<th scope="col"><?php esc_html_e( 'النموذج', 'deco-store-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'الحقول', 'deco-store-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'الحالة', 'deco-store-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'إجراءات', 'deco-store-core' ); ?></th>
			</tr></thead>
			<tbody>
			<?php foreach ( $forms as $f ) : ?>
				<tr>
					<th scope="row"><a href="<?php echo esc_url( self::url( array( 'form' => $f['id'] ) ) ); ?>"><?php echo esc_html( $f['name'] ); ?></a></th>
					<td><?php echo esc_html( (string) $f['fields'] ); ?></td>
					<td><?php echo $f['id'] === $default ? '<span class="ds-badge ds-badge--ok">✓ ' . esc_html__( 'افتراضي', 'deco-store-core' ) . '</span>' : ''; ?></td>
					<td class="ds-row-actions">
						<a class="button button-small" href="<?php echo esc_url( self::url( array( 'form' => $f['id'] ) ) ); ?>"><?php esc_html_e( 'تعديل', 'deco-store-core' ); ?></a>
						<form method="post" class="ds-inline"><?php self::form_fields( 'duplicate_form' ); ?><input type="hidden" name="form_id" value="<?php echo esc_attr( (string) $f['id'] ); ?>"><button type="submit" class="button button-small"><?php esc_html_e( 'نسخ', 'deco-store-core' ); ?></button></form>
						<?php if ( $f['id'] !== $default ) : ?>
							<form method="post" class="ds-inline"><?php self::form_fields( 'default_form' ); ?><input type="hidden" name="form_id" value="<?php echo esc_attr( (string) $f['id'] ); ?>"><button type="submit" class="button button-small"><?php esc_html_e( 'تعيين كافتراضي', 'deco-store-core' ); ?></button></form>
							<form method="post" class="ds-inline"><?php self::form_fields( 'delete_form' ); ?><input type="hidden" name="form_id" value="<?php echo esc_attr( (string) $f['id'] ); ?>"><button type="submit" class="button button-small button-link-delete"><?php esc_html_e( 'حذف', 'deco-store-core' ); ?></button></form>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		</div>
		<h2><?php esc_html_e( 'نموذج جديد', 'deco-store-core' ); ?></h2>
		<form method="post" class="ds-inline-form">
			<?php self::form_fields( 'create_form' ); ?>
			<label for="ds-new-name" class="screen-reader-text"><?php esc_html_e( 'اسم النموذج', 'deco-store-core' ); ?></label>
			<input type="text" id="ds-new-name" name="name" class="regular-text" placeholder="<?php esc_attr_e( 'اسم النموذج', 'deco-store-core' ); ?>" required>
			<?php submit_button( __( 'إنشاء', 'deco-store-core' ), 'secondary', 'submit', false ); ?>
			<span class="description"><?php esc_html_e( 'يبدأ بنسخة من الحقول الأساسية.', 'deco-store-core' ); ?></span>
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
			$name  = isset( $draft['input']['name'] ) ? \DecoStore\Core\Text::line( $draft['input']['name'], 100 ) : $name;
			$label = isset( $draft['input']['settings']['submit_label'] ) ? \DecoStore\Core\Text::line( $draft['input']['settings']['submit_label'], 60 ) : $label;
		}
		?>
		<p><a href="<?php echo esc_url( self::url() ); ?>">&larr; <?php esc_html_e( 'كل النماذج', 'deco-store-core' ); ?></a></p>
		<form method="post" class="ds-builder-form" id="ds-builder-form">
			<?php self::form_fields( 'save_form' ); ?>
			<input type="hidden" name="form_id" value="<?php echo esc_attr( (string) $form_id ); ?>">
			<input type="hidden" name="fields_json" id="ds-fields-json" value="">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="ds-form-name"><?php esc_html_e( 'اسم النموذج', 'deco-store-core' ); ?></label></th>
					<td><input type="text" id="ds-form-name" name="name" class="regular-text" value="<?php echo esc_attr( $name ); ?>" required></td>
				</tr>
				<tr>
					<th scope="row"><label for="ds-submit-label"><?php esc_html_e( 'نص زر الإرسال', 'deco-store-core' ); ?></label></th>
					<td><input type="text" id="ds-submit-label" name="submit_label" class="regular-text" value="<?php echo esc_attr( $label ); ?>"></td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'الحقول', 'deco-store-core' ); ?></h2>
			<p class="description"><?php esc_html_e( 'رتّب الحقول بأزرار الأسهم. الحقول الأساسية لا تُحذف. تحقق الخادم من كل نموذج عند الحفظ ولن يُحفظ نموذج غير صالح.', 'deco-store-core' ); ?></p>
			<div id="ds-builder" class="ds-builder" aria-label="<?php esc_attr_e( 'حقول النموذج', 'deco-store-core' ); ?>"></div>
			<p class="ds-builder-add">
				<label for="ds-add-type"><?php esc_html_e( 'إضافة حقل:', 'deco-store-core' ); ?></label>
				<select id="ds-add-type">
					<?php foreach ( FieldTypes::addable() as $type ) : ?>
						<option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( FieldTypes::label( $type ) ); ?></option>
					<?php endforeach; ?>
				</select>
				<button type="button" class="button" id="ds-add-field"><?php esc_html_e( 'إضافة', 'deco-store-core' ); ?></button>
			</p>
			<p class="screen-reader-text" id="ds-builder-status" role="status" aria-live="polite"></p>
			<?php submit_button( __( 'حفظ النموذج', 'deco-store-core' ) ); ?>
		</form>

		<h2><?php esc_html_e( 'معاينة (آخر نسخة محفوظة)', 'deco-store-core' ); ?></h2>
		<p class="description"><?php esc_html_e( 'تعمل الشروط والقوائم المرتبطة كما ستعمل في الصفحة. لا يُرسَل أي طلب من المعاينة.', 'deco-store-core' ); ?></p>
		<div class="ds-card ds-form-preview"><?php echo FormRenderer::render( $form, array( 'preview' => true ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside FormRenderer. ?></div>
		<?php
	}
}
