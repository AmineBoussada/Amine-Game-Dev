<?php
/**
 * Optional demo content: 16 sample listings, 4 agents, 3 testimonials and the standard pages.
 *
 * Run it from Appearance > Demo content. Everything it creates is flagged, so the same screen
 * can remove it again. Running it twice does not create duplicates.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Map the old feature ids used in the sample data to term slugs.
 *
 * @return array<string,string>
 */
function tassili_demo_feature_map() {
	return array(
		'ascenseur'     => 'ascenseur',
		'parking'       => 'parking',
		'vue_mer'       => 'vue-mer',
		'piscine'       => 'piscine',
		'jardin'        => 'jardin',
		'meuble'        => 'meuble',
		'terrasse'      => 'terrasse',
		'climatisation' => 'climatisation',
		'chauffage'     => 'chauffage-central',
		'gaz'           => 'gaz-de-ville',
		'citerne'       => 'citerne-deau',
		'securite'      => 'gardiennage',
		'garage'        => 'garage',
		'cuisine'       => 'cuisine-equipee',
		'viabilise'     => 'terrain-viabilise',
		'vitrine'       => 'vitrine',
	);
}

/**
 * Read a demo JSON file.
 *
 * @param string $name File name in inc/demo.
 * @return array
 */
function tassili_demo_json( $name ) {
	$path = TASSILI_DIR . 'inc/demo/' . $name;
	if ( ! is_readable( $path ) ) {
		return array();
	}
	$data = json_decode( (string) file_get_contents( $path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme file.
	return is_array( $data ) ? $data : array();
}

/**
 * Find a demo post by a meta value.
 *
 * @param string $post_type Post type.
 * @param string $meta_key  Meta key.
 * @param string $value     Meta value.
 * @return int Post ID or 0.
 */
function tassili_demo_find( $post_type, $meta_key, $value ) {
	$found = get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'any',
			'meta_key'       => $meta_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- one-off admin action.
			'meta_value'     => $value, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'fields'         => 'ids',
			'posts_per_page' => 1,
		)
	);
	return $found ? (int) $found[0] : 0;
}

/**
 * Create a page unless one with the same slug exists.
 *
 * @param string $title    Title.
 * @param string $slug     Slug.
 * @param string $template Page template file, or empty.
 * @param string $title_ar Title in Arabic.
 * @return int Page ID.
 */
function tassili_demo_page( $title, $slug, $template = '', $title_ar = '' ) {
	$existing = get_page_by_path( $slug );
	if ( $existing ) {
		return (int) $existing->ID;
	}
	$id = wp_insert_post(
		array(
			'post_type'   => 'page',
			'post_status' => 'publish',
			'post_title'  => $title,
			'post_name'   => $slug,
		)
	);
	if ( $id && ! is_wp_error( $id ) ) {
		update_post_meta( $id, '_tassili_demo', 1 );
		if ( '' !== $title_ar ) {
			update_post_meta( $id, '_tassili_title_ar', $title_ar );
		}
		if ( '' !== $template ) {
			update_post_meta( $id, '_wp_page_template', $template );
		}
		return (int) $id;
	}
	return 0;
}

/**
 * Import the demo content.
 *
 * @return array<string,int> Counts of what was created.
 */
function tassili_import_demo() {
	$counts = array(
		'properties'   => 0,
		'agents'       => 0,
		'testimonials' => 0,
		'pages'        => 0,
	);

	tassili_seed_terms();

	// Communes.
	$by_code = array();
	foreach ( tassili_wilaya_list() as $w ) {
		$by_code[ $w[0] ] = $w[1];
	}
	foreach ( tassili_demo_json( 'communes.json' ) as $w ) {
		$parent = get_term_by( 'slug', sanitize_title( isset( $by_code[ $w['code'] ] ) ? $by_code[ $w['code'] ] : $w['fr'] ), 'wilaya' );
		if ( ! $parent instanceof WP_Term ) {
			continue;
		}
		foreach ( $w['communes'] as $c ) {
			tassili_ensure_term( $c['fr'], 'wilaya', $c['ar'], (int) $parent->term_id );
		}
	}

	// Agents.
	$agent_ids = array();
	foreach ( tassili_demo_json( 'agents.json' ) as $i => $a ) {
		$existing = tassili_demo_find( 'agent', '_tassili_whatsapp', (string) $a['whatsapp'] );
		if ( $existing ) {
			$agent_ids[ $a['id'] ] = $existing;
			continue;
		}
		$id = wp_insert_post(
			array(
				'post_type'   => 'agent',
				'post_status' => 'publish',
				'post_title'  => $a['name'],
				'menu_order'  => $i,
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_tassili_demo', 1 );
			update_post_meta( $id, '_tassili_role', $a['role']['fr'] );
			update_post_meta( $id, '_tassili_role_ar', $a['role']['ar'] );
			update_post_meta( $id, '_tassili_phone', $a['phone'] );
			update_post_meta( $id, '_tassili_whatsapp', $a['whatsapp'] );
			update_post_meta( $id, '_tassili_langs', $a['langs']['fr'] );
			update_post_meta( $id, '_tassili_langs_ar', $a['langs']['ar'] );
			$agent_ids[ $a['id'] ] = (int) $id;
			++$counts['agents'];
		}
	}

	// Listings.
	$type_slug = array(
		'appartement' => 'appartement',
		'villa'       => 'villa',
		'duplex'      => 'duplex',
		'terrain'     => 'terrain',
		'local'       => 'local-commercial',
	);
	$features  = tassili_demo_feature_map();
	$rows      = tassili_demo_json( 'properties.json' );
	$newest    = 0;
	foreach ( $rows as $row ) {
		$newest = max( $newest, strtotime( $row['date'] ) );
	}
	foreach ( $rows as $row ) {
		if ( tassili_demo_find( 'property', '_tassili_ref', $row['ref'] ) ) {
			continue;
		}
		$days_old = (int) round( ( $newest - strtotime( $row['date'] ) ) / DAY_IN_SECONDS );
		$when     = wp_date( 'Y-m-d H:i:s', time() - ( $days_old + 1 ) * DAY_IN_SECONDS - 3600 );
		$id       = wp_insert_post(
			array(
				'post_type'    => 'property',
				'post_status'  => 'publish',
				'post_title'   => $row['title']['fr'],
				'post_content' => $row['desc']['fr'],
				'post_date'    => $when,
			)
		);
		if ( ! $id || is_wp_error( $id ) ) {
			continue;
		}
		$meta = array(
			'demo'        => 1,
			'ref'         => $row['ref'],
			'price'       => (int) $row['price'],
			'surface'     => (int) $row['surface'],
			'land'        => isset( $row['land'] ) ? (int) $row['land'] : null,
			'rooms'       => isset( $row['rooms'] ) ? (int) $row['rooms'] : null,
			'beds'        => isset( $row['beds'] ) ? (int) $row['beds'] : null,
			'baths'       => isset( $row['baths'] ) ? (int) $row['baths'] : null,
			'floor'       => isset( $row['floor'] ) ? (int) $row['floor'] : null,
			'floors'      => isset( $row['floors'] ) ? (int) $row['floors'] : null,
			'year'        => isset( $row['year'] ) ? (int) $row['year'] : null,
			'typo'        => isset( $row['typo'] ) ? $row['typo'] : null,
			'is_new'      => ! empty( $row['isNew'] ) ? 1 : 0,
			'delivery'    => isset( $row['delivery'] ) ? $row['delivery']['fr'] : null,
			'delivery_ar' => isset( $row['delivery'] ) ? $row['delivery']['ar'] : null,
			'featured'    => ! empty( $row['featured'] ) ? 1 : 0,
			'exclusive'   => ! empty( $row['exclusive'] ) ? 1 : 0,
			'papers'      => array_values( (array) $row['papers'] ),
			'agent'       => isset( $agent_ids[ $row['agent'] ] ) ? $agent_ids[ $row['agent'] ] : 0,
			'title_ar'    => $row['title']['ar'],
			'desc_ar'     => $row['desc']['ar'],
		);
		foreach ( $meta as $key => $value ) {
			if ( null !== $value ) {
				update_post_meta( $id, '_tassili_' . $key, $value );
			}
		}

		wp_set_object_terms( $id, array( 'vente' === $row['tx'] ? 'vente' : 'location' ), 'transaction' );
		wp_set_object_terms( $id, array( isset( $type_slug[ $row['type'] ] ) ? $type_slug[ $row['type'] ] : 'appartement' ), 'property_type' );
		$feature_slugs = array();
		foreach ( (array) $row['amen'] as $amen ) {
			if ( isset( $features[ $amen ] ) ) {
				$feature_slugs[] = $features[ $amen ];
			}
		}
		wp_set_object_terms( $id, $feature_slugs, 'feature' );

		$wilaya_name = isset( $by_code[ (int) $row['wilaya'] ] ) ? $by_code[ (int) $row['wilaya'] ] : '';
		$wilaya      = get_term_by( 'slug', sanitize_title( $wilaya_name ), 'wilaya' );
		$location    = array();
		if ( $wilaya instanceof WP_Term ) {
			$location[] = (int) $wilaya->term_id;
			foreach ( tassili_demo_json( 'communes.json' ) as $w ) {
				if ( (int) $w['code'] !== (int) $row['wilaya'] ) {
					continue;
				}
				foreach ( $w['communes'] as $c ) {
					if ( $c['id'] === $row['commune'] ) {
						$commune = get_term_by( 'slug', sanitize_title( $c['fr'] ), 'wilaya' );
						if ( $commune instanceof WP_Term ) {
							$location[] = (int) $commune->term_id;
						}
					}
				}
			}
		}
		wp_set_object_terms( $id, $location, 'wilaya' );
		++$counts['properties'];
	}

	// Testimonials.
	$testimonials = array(
		array(
			'name'     => 'Amel et Riad',
			'name_ar'  => 'أمال ورياض',
			'place'    => 'Acheteurs, Alger',
			'place_ar' => 'مشتريان، الجزائر',
			'quote'    => 'Nous avons trouvé notre F4 à Bab Ezzouar en trois semaines. Les papiers étaient déjà vérifiés, la signature chez le notaire s’est faite sans surprise.',
			'quote_ar' => 'وجدنا شقتنا F4 في باب الزوار خلال ثلاثة أسابيع. كانت الوثائق مُتحققًا منها مسبقًا، وتم التوقيع عند الموثق دون مفاجآت.',
		),
		array(
			'name'     => 'Mourad',
			'name_ar'  => 'مراد',
			'place'    => 'Propriétaire, El Biar',
			'place_ar' => 'مالك، الأبيار',
			'quote'    => 'J’ai loué mon local d’El Biar en un mois. Yasmine m’envoyait le compte rendu de chaque visite sur WhatsApp.',
			'quote_ar' => 'أجّرت محلي في الأبيار خلال شهر. كانت ياسمين ترسل لي تقرير كل زيارة على واتساب.',
		),
		array(
			'name'     => 'Sofiane et Lila',
			'name_ar'  => 'سفيان وليلى',
			'place'    => 'Acheteurs, Lyon',
			'place_ar' => 'مشتريان، ليون',
			'quote'    => 'Installés à l’étranger, nous avons acheté à Tichy sans multiplier les allers-retours. Visite vidéo, dossier complet : tout était clair.',
			'quote_ar' => 'ونحن مقيمون في الخارج، اشترينا في تيشي دون كثرة التنقل. زيارة بالفيديو وملف كامل، كان كل شيء واضحًا.',
		),
	);
	foreach ( $testimonials as $i => $t ) {
		if ( tassili_demo_find( 'testimonial', '_tassili_place', $t['place'] ) ) {
			continue;
		}
		$id = wp_insert_post(
			array(
				'post_type'    => 'testimonial',
				'post_status'  => 'publish',
				'post_title'   => $t['name'],
				'post_content' => $t['quote'],
				'menu_order'   => $i,
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_tassili_demo', 1 );
			update_post_meta( $id, '_tassili_place', $t['place'] );
			update_post_meta( $id, '_tassili_place_ar', $t['place_ar'] );
			update_post_meta( $id, '_tassili_title_ar', $t['name_ar'] );
			update_post_meta( $id, '_tassili_quote_ar', $t['quote_ar'] );
			++$counts['testimonials'];
		}
	}

	// Pages.
	$home            = tassili_demo_page( 'Accueil', 'accueil', '', 'الرئيسية' );
	$pages           = array(
		tassili_demo_page( 'L’agence', 'a-propos', 'templates/page-about.php', 'الوكالة' ),
		tassili_demo_page( 'Contact', 'contact', 'templates/page-contact.php', 'اتصل بنا' ),
		tassili_demo_page( 'Favoris', 'favoris', 'templates/page-favorites.php', 'المفضلة' ),
	);
	$counts['pages'] = count( array_filter( array_merge( array( $home ), $pages ) ) );

	if ( $home ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home );
	}
	if ( '' === (string) get_option( 'permalink_structure' ) ) {
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
	}
	if ( in_array( get_option( 'blogname' ), array( '', 'My WordPress Blog', 'WordPress', 'Tassili Test' ), true ) ) {
		update_option( 'blogname', 'Tassili' );
		update_option( 'blogdescription', 'Immobilier' );
	}
	tassili_schedule_rewrite_flush();
	return $counts;
}

/**
 * Remove everything the importer created.
 *
 * @return int Number of items removed.
 */
function tassili_remove_demo() {
	$ids   = get_posts(
		array(
			'post_type'      => array( 'property', 'agent', 'testimonial', 'page' ),
			'post_status'    => 'any',
			'meta_key'       => '_tassili_demo', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- one-off admin action.
			'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'fields'         => 'ids',
			'posts_per_page' => -1,
		)
	);
	$front = (int) get_option( 'page_on_front' );
	foreach ( $ids as $id ) {
		if ( $id === $front ) {
			update_option( 'show_on_front', 'posts' );
			update_option( 'page_on_front', 0 );
		}
		wp_delete_post( $id, true );
	}
	return count( $ids );
}

/**
 * Whether demo content is present.
 *
 * @return bool
 */
function tassili_has_demo() {
	return (bool) tassili_demo_find( 'property', '_tassili_demo', '1' );
}

/*
---------------------------------------------------------------------------------------
 * Admin screen
 * ------------------------------------------------------------------------------------
 */

/**
 * Add the screen under Appearance.
 */
function tassili_demo_menu() {
	add_theme_page(
		__( 'Demo content', 'tassili-immobilier' ),
		__( 'Demo content', 'tassili-immobilier' ),
		'manage_options',
		'tassili-demo',
		'tassili_demo_screen'
	);
}
add_action( 'admin_menu', 'tassili_demo_menu' );

/**
 * Print the screen.
 */
function tassili_demo_screen() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- only picks which notice to show.
	$done    = isset( $_GET['tassili_done'] ) ? sanitize_key( wp_unslash( $_GET['tassili_done'] ) ) : '';
	$count_n = isset( $_GET['n'] ) ? absint( $_GET['n'] ) : 0;
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Tassili Immobilier demo content', 'tassili-immobilier' ); ?></h1>
		<?php if ( 'imported' === $done ) : ?>
			<div class="notice notice-success is-dismissible"><p>
				<?php
				printf(
					/* translators: %d: number of listings created. */
					esc_html__( 'Demo content imported. %d listings were created.', 'tassili-immobilier' ),
					(int) $count_n
				);
				?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'View your site', 'tassili-immobilier' ); ?></a>
			</p></div>
		<?php elseif ( 'removed' === $done ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Demo content removed.', 'tassili-immobilier' ); ?></p></div>
		<?php endif; ?>

		<p><?php esc_html_e( 'Adds 16 sample listings across 7 wilayas, 4 agents, 3 testimonials and the About, Contact and Favourites pages, and sets the home page. Listings show built-in illustrations until you add real photos.', 'tassili-immobilier' ); ?></p>
		<p><?php esc_html_e( 'Running the import twice does not create duplicates. Everything it creates can be removed again with one click.', 'tassili-immobilier' ); ?></p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-inline-end:12px">
			<input type="hidden" name="action" value="tassili_admin_import_demo" />
			<?php wp_nonce_field( 'tassili_admin_demo' ); ?>
			<?php submit_button( __( 'Import demo content', 'tassili-immobilier' ), 'primary', 'submit', false ); ?>
		</form>
		<?php if ( tassili_has_demo() ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block">
				<input type="hidden" name="action" value="tassili_admin_remove_demo" />
				<?php wp_nonce_field( 'tassili_admin_demo' ); ?>
				<?php submit_button( __( 'Remove demo content', 'tassili-immobilier' ), 'secondary', 'submit', false ); ?>
			</form>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Handle the import button.
 */
function tassili_admin_import_demo() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do this.', 'tassili-immobilier' ), 403 );
	}
	check_admin_referer( 'tassili_admin_demo' );
	$counts = tassili_import_demo();
	wp_safe_redirect(
		add_query_arg(
			array(
				'page'         => 'tassili-demo',
				'tassili_done' => 'imported',
				'n'            => (int) $counts['properties'],
			),
			admin_url( 'themes.php' )
		)
	);
	exit;
}
add_action( 'admin_post_tassili_admin_import_demo', 'tassili_admin_import_demo' );

/**
 * Handle the remove button.
 */
function tassili_admin_remove_demo() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do this.', 'tassili-immobilier' ), 403 );
	}
	check_admin_referer( 'tassili_admin_demo' );
	tassili_remove_demo();
	wp_safe_redirect(
		add_query_arg(
			array(
				'page'         => 'tassili-demo',
				'tassili_done' => 'removed',
			),
			admin_url( 'themes.php' )
		)
	);
	exit;
}
add_action( 'admin_post_tassili_admin_remove_demo', 'tassili_admin_remove_demo' );

/**
 * Invite the owner to import the demo after activation, until there are listings.
 */
function tassili_demo_notice() {
	if ( ! current_user_can( 'manage_options' ) || get_user_meta( get_current_user_id(), 'tassili_demo_notice_dismissed', true ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->id, array( 'dashboard', 'themes' ), true ) ) {
		return;
	}
	$has_listings = get_posts(
		array(
			'post_type'      => 'property',
			'post_status'    => 'any',
			'fields'         => 'ids',
			'posts_per_page' => 1,
		)
	);
	if ( $has_listings ) {
		return;
	}
	$dismiss = wp_nonce_url( add_query_arg( 'tassili_dismiss_demo', '1' ), 'tassili_dismiss_demo' );
	?>
	<div class="notice notice-info">
		<p>
			<?php esc_html_e( 'Tassili Immobilier is active. Want to see it with sample listings?', 'tassili-immobilier' ); ?>
			<a href="<?php echo esc_url( admin_url( 'themes.php?page=tassili-demo' ) ); ?>"><strong><?php esc_html_e( 'Import demo content', 'tassili-immobilier' ); ?></strong></a>
			&middot; <a href="<?php echo esc_url( $dismiss ); ?>"><?php esc_html_e( 'Dismiss', 'tassili-immobilier' ); ?></a>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'tassili_demo_notice' );

/**
 * Remember the dismissal.
 */
function tassili_demo_notice_dismiss() {
	if ( isset( $_GET['tassili_dismiss_demo'] ) && check_admin_referer( 'tassili_dismiss_demo' ) ) {
		update_user_meta( get_current_user_id(), 'tassili_demo_notice_dismissed', 1 );
		wp_safe_redirect( remove_query_arg( array( 'tassili_dismiss_demo', '_wpnonce' ) ) );
		exit;
	}
}
add_action( 'admin_init', 'tassili_demo_notice_dismiss' );
