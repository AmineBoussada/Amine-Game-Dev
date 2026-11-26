<?php
/**
 * Contact and visit-request forms.
 *
 * Flow: the browser validates first (assets/js/forms.js), then posts to admin-ajax.php and
 * shows the result inline. Without JavaScript the form posts to admin-post.php and the visitor
 * is redirected back. Both paths run the same server checks: nonce, honeypot, rate limit,
 * sanitising and validation. Valid requests are stored as private "lead" posts and e-mailed.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Subjects of the contact form.
 *
 * @return array<string,string>
 */
function tassili_contact_subjects() {
	return array(
		'buy'      => __( 'Buy a property', 'tassili-immobilier' ),
		'rent'     => __( 'Rent a property', 'tassili-immobilier' ),
		'sell'     => __( 'Sell or let my property', 'tassili-immobilier' ),
		'estimate' => __( 'Get my property valued', 'tassili-immobilier' ),
		'other'    => __( 'Another question', 'tassili-immobilier' ),
	);
}

/**
 * Print the hidden fields every form needs: action, nonce and the honeypot.
 *
 * @param string $kind contact|visit.
 */
function tassili_form_fields( $kind ) {
	printf( '<input type="hidden" name="action" value="%s" />', esc_attr( 'tassili_' . $kind ) );
	wp_nonce_field( 'tassili_form_' . $kind, '_tassili_nonce' );
	// Honeypot: invisible to people, tempting to bots.
	printf(
		'<div class="tassili-hp" aria-hidden="true" style="position:absolute;inset-inline-start:-9999px;width:1px;height:1px;overflow:hidden"><label>%1$s<input type="text" name="website" value="" tabindex="-1" autocomplete="off" /></label></div>',
		esc_html__( 'Leave this field empty', 'tassili-immobilier' )
	);
}

/**
 * Normalise an Algerian phone number for checking.
 *
 * @param string $phone Phone as typed.
 * @return string
 */
function tassili_normalize_phone( $phone ) {
	return preg_replace( '/[\s.\-()]/', '', (string) $phone );
}

/**
 * Whether a phone number is a valid Algerian mobile number (05, 06, 07).
 *
 * @param string $phone Phone as typed.
 * @return bool
 */
function tassili_is_valid_phone( $phone ) {
	return 1 === preg_match( '/^(?:\+213|00213|0)[567]\d{8}$/', tassili_normalize_phone( $phone ) );
}

/**
 * Format a date for confirmation messages (uses PHP intl when present).
 *
 * @param DateTimeInterface $date Date.
 * @return string
 */
function tassili_format_date( $date ) {
	if ( class_exists( 'IntlDateFormatter' ) ) {
		$locale = tassili_is_ar() ? 'ar-DZ' : 'fr-FR';
		$fmt    = new IntlDateFormatter( $locale, IntlDateFormatter::FULL, IntlDateFormatter::NONE, $date->getTimezone(), IntlDateFormatter::GREGORIAN, 'EEEE d MMMM' );
		$out    = $fmt ? $fmt->format( $date ) : false;
		if ( is_string( $out ) && '' !== $out ) {
			return $out;
		}
	}
	return $date->format( 'd/m/Y' );
}

/**
 * Read one posted value.
 *
 * @param string $key Field name.
 * @return string
 */
function tassili_posted( $key ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- the nonce is verified in tassili_handle_form() before any field is read; each caller sanitises by field type.
	return isset( $_POST[ $key ] ) && ! is_array( $_POST[ $key ] ) ? wp_unslash( (string) $_POST[ $key ] ) : '';
}

/**
 * Throttle submissions per visitor.
 *
 * @return bool True when the visitor may submit.
 */
function tassili_rate_limit_ok() {
	$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$key   = 'tassili_rl_' . md5( $ip );
	$limit = (int) apply_filters( 'tassili_form_rate_limit', 5 );
	$count = (int) get_transient( $key );
	if ( $count >= $limit ) {
		return false;
	}
	set_transient( $key, $count + 1, 15 * MINUTE_IN_SECONDS );
	return true;
}

/**
 * Validate a submission.
 *
 * @param string $kind contact|visit.
 * @return array{values:array<string,string>,errors:array<string,string>}
 */
function tassili_validate_form( $kind ) {
	$values = array(
		'name'        => sanitize_text_field( tassili_posted( 'name' ) ),
		'phone'       => sanitize_text_field( tassili_posted( 'phone' ) ),
		'email'       => sanitize_email( tassili_posted( 'email' ) ),
		'subject'     => sanitize_key( tassili_posted( 'subject' ) ),
		'ref'         => strtoupper( preg_replace( '/[^A-Za-z0-9\-]/', '', tassili_posted( 'ref' ) ) ),
		'message'     => sanitize_textarea_field( tassili_posted( 'message' ) ),
		'consent'     => tassili_posted( 'consent' ) ? '1' : '',
		'date'        => sanitize_text_field( tassili_posted( 'date' ) ),
		'slot'        => sanitize_key( tassili_posted( 'slot' ) ),
		'property_id' => (string) absint( tassili_posted( 'property_id' ) ),
	);
	$errors = array();

	if ( preg_match_all( '/\p{L}/u', $values['name'] ) < 2 ) {
		$errors['name'] = '' === $values['name'] ? __( 'This field is required.', 'tassili-immobilier' ) : __( 'Enter at least 2 letters.', 'tassili-immobilier' );
	}
	if ( '' === $values['phone'] ) {
		$errors['phone'] = __( 'This field is required.', 'tassili-immobilier' );
	} elseif ( ! tassili_is_valid_phone( $values['phone'] ) ) {
		$errors['phone'] = __( 'Enter an Algerian number, for example 0555 12 34 56.', 'tassili-immobilier' );
	}
	if ( '' !== tassili_posted( 'email' ) && ! is_email( $values['email'] ) ) {
		$errors['email'] = __( 'Enter a valid e-mail address, for example name@example.dz.', 'tassili-immobilier' );
	}

	if ( 'contact' === $kind ) {
		if ( ! array_key_exists( $values['subject'], tassili_contact_subjects() ) ) {
			$values['subject'] = 'other';
		}
		if ( '' === $values['message'] ) {
			$errors['message'] = __( 'This field is required.', 'tassili-immobilier' );
		} elseif ( mb_strlen( $values['message'] ) < 10 ) {
			$errors['message'] = __( 'Write at least 10 characters.', 'tassili-immobilier' );
		}
		if ( '' === $values['consent'] ) {
			$errors['consent'] = __( 'Tick this box so that we can answer you.', 'tassili-immobilier' );
		}
		if ( '' !== $values['ref'] ) {
			$values['ref'] = substr( $values['ref'], 0, 20 );
		}
	} else {
		$property = get_post( (int) $values['property_id'] );
		if ( ! $property || 'property' !== $property->post_type || 'publish' !== $property->post_status ) {
			$errors['property_id'] = __( 'This property is no longer available.', 'tassili-immobilier' );
		} else {
			$values['ref'] = (string) get_post_meta( $property->ID, '_tassili_ref', true );
		}
		$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $values['date'], wp_timezone() );
		if ( ! $date || $date->format( 'Y-m-d' ) !== $values['date'] ) {
			$errors['date'] = '' === $values['date'] ? __( 'This field is required.', 'tassili-immobilier' ) : __( 'Choose a date from tomorrow.', 'tassili-immobilier' );
		} else {
			$tomorrow = ( new DateTimeImmutable( 'today', wp_timezone() ) )->modify( '+1 day' );
			if ( $date < $tomorrow ) {
				$errors['date'] = __( 'Choose a date from tomorrow.', 'tassili-immobilier' );
			} elseif ( (int) $date->format( 'w' ) === (int) tassili_mod( 'closed_weekday' ) ) {
				$errors['date'] = __( 'The agency is closed that day. Choose another day.', 'tassili-immobilier' );
			}
		}
		if ( ! in_array( $values['slot'], array( 'am', 'pm' ), true ) ) {
			$values['slot'] = 'am';
		}
		$values['subject'] = 'visit';
	}
	$values['message'] = mb_substr( $values['message'], 0, 4000 );
	return array(
		'values' => $values,
		'errors' => $errors,
	);
}

/**
 * Store a valid request and e-mail it.
 *
 * @param string $kind   contact|visit.
 * @param array  $values Validated values.
 * @return array{name:string,phone:string,text:string,title:string}
 */
function tassili_store_and_notify( $kind, $values ) {
	$subjects = tassili_contact_subjects();
	$label    = 'visit' === $kind ? __( 'Visit request', 'tassili-immobilier' ) : $subjects[ $values['subject'] ];

	$lead_id = wp_insert_post(
		array(
			'post_type'   => 'lead',
			'post_status' => 'private',
			'post_title'  => trim( $values['name'] . ' — ' . $label ),
		),
		true
	);
	if ( ! is_wp_error( $lead_id ) ) {
		$meta = array(
			'kind'     => $kind,
			'phone'    => $values['phone'],
			'email'    => $values['email'],
			'subject'  => $label,
			'ref'      => $values['ref'],
			'message'  => $values['message'],
			'date'     => $values['date'],
			'slot'     => $values['slot'],
			'lang'     => tassili_lang(),
			'property' => $values['property_id'],
		);
		foreach ( $meta as $key => $value ) {
			if ( '' !== $value ) {
				update_post_meta( $lead_id, '_tassili_' . $key, $value );
			}
		}
	}

	$to = sanitize_email( (string) tassili_mod( 'notify_email' ) );
	$to = is_email( $to ) ? $to : get_option( 'admin_email' );

	$lines = array(
		/* translators: %s: type of request. */
		sprintf( __( 'New request: %s', 'tassili-immobilier' ), $label ),
		'',
		__( 'Name', 'tassili-immobilier' ) . ': ' . $values['name'],
		__( 'Phone', 'tassili-immobilier' ) . ': ' . $values['phone'],
	);
	if ( '' !== $values['email'] ) {
		$lines[] = __( 'E-mail', 'tassili-immobilier' ) . ': ' . $values['email'];
	}
	if ( '' !== $values['ref'] ) {
		$lines[] = __( 'Property reference', 'tassili-immobilier' ) . ': ' . $values['ref'];
	}
	if ( 'visit' === $kind ) {
		$lines[] = __( 'Visit date', 'tassili-immobilier' ) . ': ' . $values['date'] . ' (' . ( 'pm' === $values['slot'] ? __( 'afternoon', 'tassili-immobilier' ) : __( 'morning', 'tassili-immobilier' ) ) . ')';
	}
	if ( '' !== $values['message'] ) {
		$lines[] = '';
		$lines[] = $values['message'];
	}
	if ( ! is_wp_error( $lead_id ) ) {
		$lines[] = '';
		$lines[] = admin_url( 'post.php?post=' . (int) $lead_id . '&action=edit' );
	}

	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
	if ( is_email( $values['email'] ) ) {
		$safe_name = trim( preg_replace( '/[\r\n"<>,;]+/', ' ', $values['name'] ) );
		$headers[] = 'Reply-To: ' . $safe_name . ' <' . $values['email'] . '>';
	}
	wp_mail(
		$to,
		/* translators: 1: site name, 2: type of request, 3: sender name. */
		sprintf( __( '[%1$s] %2$s from %3$s', 'tassili-immobilier' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $label, $values['name'] ),
		implode( "\n", $lines ),
		$headers
	);

	if ( 'visit' === $kind ) {
		$agent = tassili_get_agent( (int) get_post_meta( (int) $values['property_id'], '_tassili_agent', true ) );
		$date  = DateTimeImmutable::createFromFormat( '!Y-m-d', $values['date'], wp_timezone() );
		return array(
			'name'  => $values['name'],
			'phone' => $values['phone'],
			'title' => __( 'Request sent', 'tassili-immobilier' ),
			'text'  => sprintf(
				/* translators: 1: visitor name, 2: agent name, 3: visitor phone, 4: visit date. */
				__( 'Thank you %1$s. %2$s will call you on %3$s to confirm your visit on %4$s.', 'tassili-immobilier' ),
				$values['name'],
				$agent ? $agent['name'] : get_bloginfo( 'name' ),
				$values['phone'],
				$date ? tassili_format_date( $date ) : $values['date']
			),
		);
	}
	return array(
		'name'  => $values['name'],
		'phone' => $values['phone'],
		'title' => __( 'Message sent', 'tassili-immobilier' ),
		'text'  => sprintf(
			/* translators: 1: visitor name, 2: visitor phone. */
			__( 'Thank you %1$s. We will answer within 24 working hours on %2$s.', 'tassili-immobilier' ),
			$values['name'],
			$values['phone']
		),
	);
}

/**
 * Handle a form submission from either entry point.
 *
 * @param string $kind contact|visit.
 */
function tassili_handle_form( $kind ) {
	$is_ajax = wp_doing_ajax();

	$fail = static function ( $errors, $message, $status = 422 ) use ( $is_ajax, $kind ) {
		if ( $is_ajax ) {
			wp_send_json_error(
				array(
					'errors'  => $errors,
					'message' => $message,
				),
				$status
			);
		}
		$fid = wp_generate_password( 12, false );
		set_transient(
			'tassili_form_' . $fid,
			array(
				'kind'    => $kind,
				'errors'  => $errors,
				'message' => $message,
				'values'  => array_map( 'sanitize_text_field', array_intersect_key( wp_unslash( $_POST ), array_flip( array( 'name', 'phone', 'email', 'subject', 'ref', 'message', 'date', 'slot' ) ) ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce checked by the caller; values sanitized by array_map.
			),
			10 * MINUTE_IN_SECONDS
		);
		wp_safe_redirect( add_query_arg( 'tassili_fid', $fid, remove_query_arg( array( 'tassili_fid', 'tassili_sent' ), tassili_form_return_url() ) ) . '#' . $kind . '-form' );
		exit;
	};

	if ( ! isset( $_POST['_tassili_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_tassili_nonce'] ) ), 'tassili_form_' . $kind ) ) {
		$fail( array(), __( 'This page has expired. Please reload it and try again.', 'tassili-immobilier' ), 403 );
	}

	/**
	 * Finish with a success message: JSON for the script, or a redirect carrying a random,
	 * unguessable token that opens the stored message once the page reloads.
	 */
	$succeed = static function ( $done ) use ( $is_ajax, $kind ) {
		if ( $is_ajax ) {
			wp_send_json_success( $done );
		}
		$token        = wp_generate_password( 16, false );
		$done['kind'] = $kind;
		set_transient( 'tassili_sent_' . $token, $done, 10 * MINUTE_IN_SECONDS );
		wp_safe_redirect( add_query_arg( 'tassili_sent', $token, remove_query_arg( array( 'tassili_fid', 'tassili_sent' ), tassili_form_return_url() ) ) . '#' . $kind . '-form' );
		exit;
	};

	// Honeypot: answer as if it worked, store nothing. It still counts towards the rate limit.
	if ( '' !== tassili_posted( 'website' ) ) {
		if ( ! tassili_rate_limit_ok() ) {
			$fail( array(), __( 'Too many requests. Please wait a few minutes or contact us on WhatsApp.', 'tassili-immobilier' ), 429 );
		}
		$succeed(
			array(
				'name'  => '',
				'phone' => '',
				'title' => __( 'Message sent', 'tassili-immobilier' ),
				'text'  => __( 'Thank you. We will answer within 24 working hours.', 'tassili-immobilier' ),
			)
		);
	}

	$result = tassili_validate_form( $kind );
	if ( $result['errors'] ) {
		$fail( $result['errors'], __( 'Check the fields marked in red.', 'tassili-immobilier' ) );
	}

	// Only complete, valid submissions count: a visitor fixing typos is never locked out.
	if ( ! tassili_rate_limit_ok() ) {
		$fail( array(), __( 'Too many requests. Please wait a few minutes or contact us on WhatsApp.', 'tassili-immobilier' ), 429 );
	}

	$succeed( tassili_store_and_notify( $kind, $result['values'] ) );
}

/**
 * Page to return to after a no-JavaScript submission.
 *
 * @return string
 */
function tassili_form_return_url() {
	$url = wp_get_referer();
	return $url ? $url : home_url( '/' );
}

/**
 * Contact form entry point.
 */
function tassili_handle_contact() {
	tassili_handle_form( 'contact' );
}
/**
 * Visit request entry point.
 */
function tassili_handle_visit() {
	tassili_handle_form( 'visit' );
}
add_action( 'admin_post_nopriv_tassili_contact', 'tassili_handle_contact' );
add_action( 'admin_post_tassili_contact', 'tassili_handle_contact' );
add_action( 'wp_ajax_nopriv_tassili_contact', 'tassili_handle_contact' );
add_action( 'wp_ajax_tassili_contact', 'tassili_handle_contact' );
add_action( 'admin_post_nopriv_tassili_visit', 'tassili_handle_visit' );
add_action( 'admin_post_tassili_visit', 'tassili_handle_visit' );
add_action( 'wp_ajax_nopriv_tassili_visit', 'tassili_handle_visit' );
add_action( 'wp_ajax_tassili_visit', 'tassili_handle_visit' );

/**
 * State of a form after a no-JavaScript round trip.
 *
 * @param string $kind contact|visit.
 * @return array{errors:array,values:array,message:string,sent:?array}
 */
function tassili_form_state( $kind ) {
	$state = array(
		'errors'  => array(),
		'values'  => array(),
		'message' => '',
		'sent'    => null,
	);
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- these arguments only choose which stored message to show.
	if ( isset( $_GET['tassili_fid'] ) ) {
		$data = get_transient( 'tassili_form_' . sanitize_key( wp_unslash( $_GET['tassili_fid'] ) ) );
		if ( is_array( $data ) && isset( $data['kind'] ) && $kind === $data['kind'] ) {
			$state['errors']  = (array) $data['errors'];
			$state['values']  = (array) $data['values'];
			$state['message'] = (string) $data['message'];
		}
	}
	if ( isset( $_GET['tassili_sent'] ) ) {
		// The value is a random token; only the person who just submitted the form has it.
		$data = get_transient( 'tassili_sent_' . sanitize_key( wp_unslash( $_GET['tassili_sent'] ) ) );
		if ( is_array( $data ) && isset( $data['kind'] ) && $kind === $data['kind'] ) {
			$state['sent'] = $data;
		}
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
	return $state;
}
