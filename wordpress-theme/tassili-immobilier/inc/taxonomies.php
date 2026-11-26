<?php
/**
 * Taxonomies: transaction, wilaya (with communes), property type and feature.
 *
 * Every term can carry an Arabic name. Wilayas also carry their official number.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register taxonomies.
 */
function tassili_register_taxonomies() {
	$common = array(
		'show_ui'           => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'query_var'         => false,
	);

	register_taxonomy(
		'transaction',
		'property',
		array_merge(
			$common,
			array(
				'labels'       => array(
					'name'          => __( 'Transactions', 'tassili-immobilier' ),
					'singular_name' => __( 'Transaction', 'tassili-immobilier' ),
					'all_items'     => __( 'All transactions', 'tassili-immobilier' ),
					'edit_item'     => __( 'Edit transaction', 'tassili-immobilier' ),
					'add_new_item'  => __( 'Add new transaction', 'tassili-immobilier' ),
					'menu_name'     => __( 'Transactions', 'tassili-immobilier' ),
				),
				'hierarchical' => true,
				'rewrite'      => array(
					'slug'       => 'transaction',
					'with_front' => false,
				),
			)
		)
	);

	register_taxonomy(
		'wilaya',
		'property',
		array_merge(
			$common,
			array(
				'labels'       => array(
					'name'          => __( 'Wilayas and communes', 'tassili-immobilier' ),
					'singular_name' => __( 'Wilaya / commune', 'tassili-immobilier' ),
					'all_items'     => __( 'All wilayas and communes', 'tassili-immobilier' ),
					'edit_item'     => __( 'Edit wilaya / commune', 'tassili-immobilier' ),
					'add_new_item'  => __( 'Add new wilaya / commune', 'tassili-immobilier' ),
					'parent_item'   => __( 'Wilaya (parent)', 'tassili-immobilier' ),
					'menu_name'     => __( 'Wilayas', 'tassili-immobilier' ),
				),
				'hierarchical' => true,
				'rewrite'      => array(
					'slug'         => 'wilaya',
					'with_front'   => false,
					'hierarchical' => true,
				),
			)
		)
	);

	register_taxonomy(
		'property_type',
		'property',
		array_merge(
			$common,
			array(
				'labels'       => array(
					'name'          => __( 'Property types', 'tassili-immobilier' ),
					'singular_name' => __( 'Property type', 'tassili-immobilier' ),
					'all_items'     => __( 'All property types', 'tassili-immobilier' ),
					'edit_item'     => __( 'Edit property type', 'tassili-immobilier' ),
					'add_new_item'  => __( 'Add new property type', 'tassili-immobilier' ),
					'menu_name'     => __( 'Property types', 'tassili-immobilier' ),
				),
				'hierarchical' => true,
				'rewrite'      => array(
					'slug'       => 'type',
					'with_front' => false,
				),
			)
		)
	);

	register_taxonomy(
		'feature',
		'property',
		array_merge(
			$common,
			array(
				'labels'            => array(
					'name'          => __( 'Features', 'tassili-immobilier' ),
					'singular_name' => __( 'Feature', 'tassili-immobilier' ),
					'all_items'     => __( 'All features', 'tassili-immobilier' ),
					'edit_item'     => __( 'Edit feature', 'tassili-immobilier' ),
					'add_new_item'  => __( 'Add new feature', 'tassili-immobilier' ),
					'menu_name'     => __( 'Features', 'tassili-immobilier' ),
				),
				'hierarchical'      => true,
				'public'            => false,
				'rewrite'           => false,
				'show_admin_column' => false,
			)
		)
	);

	foreach ( array( 'transaction', 'wilaya', 'property_type', 'feature' ) as $taxonomy ) {
		register_term_meta(
			$taxonomy,
			'tassili_name_ar',
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
			)
		);
	}
	register_term_meta(
		'wilaya',
		'tassili_code',
		array(
			'type'              => 'integer',
			'single'            => true,
			'sanitize_callback' => 'absint',
			'show_in_rest'      => true,
		)
	);
	register_term_meta(
		'property_type',
		'tassili_icon',
		array(
			'type'              => 'string',
			'single'            => true,
			'sanitize_callback' => 'tassili_sanitize_type_icon',
			'show_in_rest'      => true,
		)
	);
	register_term_meta(
		'feature',
		'tassili_filter',
		array(
			'type'              => 'boolean',
			'single'            => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'show_in_rest'      => true,
		)
	);
}
add_action( 'init', 'tassili_register_taxonomies', 0 );

/**
 * Icons a property type can use.
 *
 * @return string[]
 */
function tassili_type_icons() {
	return array( 'building', 'villa', 'duplex', 'land', 'shop' );
}

/**
 * Sanitize a property type icon name.
 *
 * @param string $value Value.
 * @return string
 */
function tassili_sanitize_type_icon( $value ) {
	return in_array( $value, tassili_type_icons(), true ) ? $value : 'building';
}

/*
---------------------------------------------------------------------------------------
 * Term screens: Arabic name and extra fields
 * ------------------------------------------------------------------------------------
 */

/**
 * Fields added to a term form.
 *
 * @param string $taxonomy Taxonomy.
 * @return array<string,array<string,mixed>>
 */
function tassili_term_fields( $taxonomy ) {
	$fields = array(
		'tassili_name_ar' => array(
			'label' => __( 'Name in Arabic', 'tassili-immobilier' ),
			'type'  => 'text',
		),
	);
	if ( 'wilaya' === $taxonomy ) {
		$fields['tassili_code'] = array(
			'label' => __( 'Wilaya number', 'tassili-immobilier' ),
			'type'  => 'number',
			'help'  => __( 'Official number, from 1 to 58. Leave empty for communes.', 'tassili-immobilier' ),
		);
	}
	if ( 'property_type' === $taxonomy ) {
		$fields['tassili_icon'] = array(
			'label'   => __( 'Icon', 'tassili-immobilier' ),
			'type'    => 'select',
			'options' => tassili_type_icons(),
		);
	}
	if ( 'feature' === $taxonomy ) {
		$fields['tassili_filter'] = array(
			'label' => __( 'Offer as a search filter', 'tassili-immobilier' ),
			'type'  => 'checkbox',
		);
	}
	return $fields;
}

/**
 * Print one term field.
 *
 * @param string $key   Meta key.
 * @param array  $field Field definition.
 * @param mixed  $value Current value.
 */
function tassili_print_term_field( $key, $field, $value ) {
	$id = 'tassili-' . $key;
	switch ( $field['type'] ) {
		case 'number':
			printf( '<input type="number" min="1" max="58" id="%1$s" name="%2$s" value="%3$s" class="small-text" />', esc_attr( $id ), esc_attr( $key ), esc_attr( (string) $value ) );
			break;
		case 'select':
			printf( '<select id="%1$s" name="%2$s">', esc_attr( $id ), esc_attr( $key ) );
			foreach ( $field['options'] as $option ) {
				printf( '<option value="%1$s"%2$s>%1$s</option>', esc_attr( $option ), selected( $value, $option, false ) );
			}
			echo '</select>';
			break;
		case 'checkbox':
			printf( '<input type="checkbox" id="%1$s" name="%2$s" value="1"%3$s />', esc_attr( $id ), esc_attr( $key ), checked( $value, true, false ) );
			break;
		default:
			printf( '<input type="text" id="%1$s" name="%2$s" value="%3$s" class="regular-text" dir="auto" />', esc_attr( $id ), esc_attr( $key ), esc_attr( (string) $value ) );
	}
	if ( ! empty( $field['help'] ) ) {
		printf( '<p class="description">%s</p>', esc_html( $field['help'] ) );
	}
}

/**
 * "Add term" form fields.
 *
 * @param string $taxonomy Taxonomy.
 */
function tassili_term_add_form( $taxonomy ) {
	wp_nonce_field( 'tassili_term_meta', 'tassili_term_nonce' );
	foreach ( tassili_term_fields( $taxonomy ) as $key => $field ) {
		echo '<div class="form-field"><label for="' . esc_attr( 'tassili-' . $key ) . '">' . esc_html( $field['label'] ) . '</label>';
		tassili_print_term_field( $key, $field, 'tassili_filter' === $key ? true : '' );
		echo '</div>';
	}
}

/**
 * "Edit term" form fields.
 *
 * @param WP_Term $term     Term.
 * @param string  $taxonomy Taxonomy.
 */
function tassili_term_edit_form( $term, $taxonomy ) {
	wp_nonce_field( 'tassili_term_meta', 'tassili_term_nonce' );
	foreach ( tassili_term_fields( $taxonomy ) as $key => $field ) {
		$value = get_term_meta( $term->term_id, $key, true );
		echo '<tr class="form-field"><th scope="row"><label for="' . esc_attr( 'tassili-' . $key ) . '">' . esc_html( $field['label'] ) . '</label></th><td>';
		tassili_print_term_field( $key, $field, $value );
		echo '</td></tr>';
	}
}

/**
 * Save the term fields.
 *
 * @param int    $term_id  Term ID.
 * @param int    $tt_id    Term taxonomy ID.
 * @param string $taxonomy Taxonomy.
 */
function tassili_save_term_meta( $term_id, $tt_id = 0, $taxonomy = '' ) {
	if ( ! isset( $_POST['tassili_term_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tassili_term_nonce'] ) ), 'tassili_term_meta' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	foreach ( tassili_term_fields( $taxonomy ) as $key => $field ) {
		if ( 'checkbox' === $field['type'] ) {
			update_term_meta( $term_id, $key, isset( $_POST[ $key ] ) ? 1 : 0 );
			continue;
		}
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}
		$raw = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
		if ( 'number' === $field['type'] ) {
			$raw = absint( $raw );
		} elseif ( 'tassili_icon' === $key ) {
			$raw = tassili_sanitize_type_icon( $raw );
		}
		update_term_meta( $term_id, $key, $raw );
	}
}

/**
 * Hook the term screens of our taxonomies.
 */
function tassili_register_term_screens() {
	foreach ( array( 'transaction', 'wilaya', 'property_type', 'feature' ) as $taxonomy ) {
		add_action( $taxonomy . '_add_form_fields', 'tassili_term_add_form' );
		add_action( $taxonomy . '_edit_form_fields', 'tassili_term_edit_form', 10, 2 );
		add_action( 'created_' . $taxonomy, 'tassili_save_term_meta', 10, 3 );
		add_action( 'edited_' . $taxonomy, 'tassili_save_term_meta', 10, 3 );
	}
}
add_action( 'admin_init', 'tassili_register_term_screens' );

/*
---------------------------------------------------------------------------------------
 * Default terms
 * ------------------------------------------------------------------------------------
 */

/**
 * The 58 wilayas: number, French name, Arabic name.
 *
 * @return array<int,array{int,string,string}>
 */
function tassili_wilaya_list() {
	return array(
		array( 1, 'Adrar', 'أدرار' ),
		array( 2, 'Chlef', 'الشلف' ),
		array( 3, 'Laghouat', 'الأغواط' ),
		array( 4, 'Oum El Bouaghi', 'أم البواقي' ),
		array( 5, 'Batna', 'باتنة' ),
		array( 6, 'Béjaïa', 'بجاية' ),
		array( 7, 'Biskra', 'بسكرة' ),
		array( 8, 'Béchar', 'بشار' ),
		array( 9, 'Blida', 'البليدة' ),
		array( 10, 'Bouira', 'البويرة' ),
		array( 11, 'Tamanrasset', 'تمنراست' ),
		array( 12, 'Tébessa', 'تبسة' ),
		array( 13, 'Tlemcen', 'تلمسان' ),
		array( 14, 'Tiaret', 'تيارت' ),
		array( 15, 'Tizi Ouzou', 'تيزي وزو' ),
		array( 16, 'Alger', 'الجزائر' ),
		array( 17, 'Djelfa', 'الجلفة' ),
		array( 18, 'Jijel', 'جيجل' ),
		array( 19, 'Sétif', 'سطيف' ),
		array( 20, 'Saïda', 'سعيدة' ),
		array( 21, 'Skikda', 'سكيكدة' ),
		array( 22, 'Sidi Bel Abbès', 'سيدي بلعباس' ),
		array( 23, 'Annaba', 'عنابة' ),
		array( 24, 'Guelma', 'قالمة' ),
		array( 25, 'Constantine', 'قسنطينة' ),
		array( 26, 'Médéa', 'المدية' ),
		array( 27, 'Mostaganem', 'مستغانم' ),
		array( 28, 'M’Sila', 'المسيلة' ),
		array( 29, 'Mascara', 'معسكر' ),
		array( 30, 'Ouargla', 'ورقلة' ),
		array( 31, 'Oran', 'وهران' ),
		array( 32, 'El Bayadh', 'البيض' ),
		array( 33, 'Illizi', 'إليزي' ),
		array( 34, 'Bordj Bou Arréridj', 'برج بوعريريج' ),
		array( 35, 'Boumerdès', 'بومرداس' ),
		array( 36, 'El Tarf', 'الطارف' ),
		array( 37, 'Tindouf', 'تندوف' ),
		array( 38, 'Tissemsilt', 'تيسمسيلت' ),
		array( 39, 'El Oued', 'الوادي' ),
		array( 40, 'Khenchela', 'خنشلة' ),
		array( 41, 'Souk Ahras', 'سوق أهراس' ),
		array( 42, 'Tipaza', 'تيبازة' ),
		array( 43, 'Mila', 'ميلة' ),
		array( 44, 'Aïn Defla', 'عين الدفلى' ),
		array( 45, 'Naâma', 'النعامة' ),
		array( 46, 'Aïn Témouchent', 'عين تموشنت' ),
		array( 47, 'Ghardaïa', 'غرداية' ),
		array( 48, 'Relizane', 'غليزان' ),
		array( 49, 'El M’Ghair', 'المغير' ),
		array( 50, 'El Menia', 'المنيعة' ),
		array( 51, 'Ouled Djellal', 'أولاد جلال' ),
		array( 52, 'Bordj Badji Mokhtar', 'برج باجي مختار' ),
		array( 53, 'Béni Abbès', 'بني عباس' ),
		array( 54, 'Timimoun', 'تيميمون' ),
		array( 55, 'Touggourt', 'تقرت' ),
		array( 56, 'Djanet', 'جانت' ),
		array( 57, 'In Salah', 'عين صالح' ),
		array( 58, 'In Guezzam', 'عين قزام' ),
	);
}

/**
 * Create a term with its Arabic name unless it exists.
 *
 * @param string $name     French (main) name.
 * @param string $taxonomy Taxonomy.
 * @param string $name_ar  Arabic name.
 * @param int    $parent_id Parent term ID.
 * @param array  $meta     Extra term meta.
 * @return int Term ID, 0 on failure.
 */
function tassili_ensure_term( $name, $taxonomy, $name_ar = '', $parent_id = 0, $meta = array() ) {
	$slug     = sanitize_title( $name );
	$existing = get_term_by( 'slug', $slug, $taxonomy );
	if ( $existing instanceof WP_Term && (int) $existing->parent === (int) $parent_id ) {
		return (int) $existing->term_id;
	}
	$result = wp_insert_term(
		$name,
		$taxonomy,
		array(
			'slug'   => $slug,
			'parent' => $parent_id,
		)
	);
	if ( is_wp_error( $result ) ) {
		$id = $result->get_error_data( 'term_exists' );
		return is_numeric( $id ) ? (int) $id : 0;
	}
	$term_id = (int) $result['term_id'];
	if ( '' !== $name_ar ) {
		update_term_meta( $term_id, 'tassili_name_ar', $name_ar );
	}
	foreach ( $meta as $key => $value ) {
		update_term_meta( $term_id, $key, $value );
	}
	return $term_id;
}

/**
 * Seed the terms every install needs. Safe to run many times.
 */
function tassili_seed_terms() {
	tassili_ensure_term( 'Vente', 'transaction', 'بيع' );
	tassili_ensure_term( 'Location', 'transaction', 'كراء' );

	$types = array(
		array( 'Appartement', 'شقة', 'building' ),
		array( 'Villa', 'فيلا', 'villa' ),
		array( 'Duplex', 'دوبلكس', 'duplex' ),
		array( 'Terrain', 'قطعة أرض', 'land' ),
		array( 'Local commercial', 'محل تجاري', 'shop' ),
	);
	foreach ( $types as $type ) {
		tassili_ensure_term( $type[0], 'property_type', $type[1], 0, array( 'tassili_icon' => $type[2] ) );
	}

	$features = array(
		array( 'Ascenseur', 'مصعد', 1 ),
		array( 'Parking', 'موقف سيارات', 1 ),
		array( 'Vue mer', 'إطلالة على البحر', 1 ),
		array( 'Piscine', 'مسبح', 1 ),
		array( 'Jardin', 'حديقة', 1 ),
		array( 'Meublé', 'مفروش', 1 ),
		array( 'Terrasse', 'تراس', 1 ),
		array( 'Climatisation', 'تكييف', 1 ),
		array( 'Chauffage central', 'تدفئة مركزية', 0 ),
		array( 'Gaz de ville', 'غاز المدينة', 0 ),
		array( 'Citerne d’eau', 'خزان مياه', 0 ),
		array( 'Gardiennage', 'حراسة', 0 ),
		array( 'Garage', 'مرآب', 0 ),
		array( 'Cuisine équipée', 'مطبخ مجهز', 0 ),
		array( 'Terrain viabilisé', 'أرض مهيأة', 0 ),
		array( 'Vitrine', 'واجهة زجاجية', 0 ),
	);
	foreach ( $features as $feature ) {
		tassili_ensure_term( $feature[0], 'feature', $feature[1], 0, array( 'tassili_filter' => $feature[2] ) );
	}

	foreach ( tassili_wilaya_list() as $wilaya ) {
		tassili_ensure_term( $wilaya[1], 'wilaya', $wilaya[2], 0, array( 'tassili_code' => $wilaya[0] ) );
	}
}
