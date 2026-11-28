/**
 * Order form behaviour (vanilla JS, no dependencies besides forms.js).
 *
 * - Fetches a fresh signed token (never cached) and makes one random
 *   idempotency key per order attempt, reused on retries so a double click
 *   or a network retry can never create two orders.
 * - Asks the server for a price preview whenever a choice changes. The
 *   browser never computes or sends a price: it only echoes the total it
 *   displayed, so the server can refuse when it changed.
 * - Sends the order when forms.js reports the form valid.
 */
( function () {
	'use strict';

	function randomKey() {
		var bytes = new Uint8Array( 18 );
		( window.crypto || window.msCrypto ).getRandomValues( bytes );
		var s = '';
		for ( var i = 0; i < bytes.length; i++ ) {
			s += String.fromCharCode( bytes[ i ] );
		}
		return btoa( s ).replace( /\+/g, '-' ).replace( /\//g, '_' ).replace( /=+$/, '' );
	}

	function json( url, method, body ) {
		var opts = { method: method, credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } };
		if ( body ) {
			opts.headers[ 'Content-Type' ] = 'application/json';
			opts.body = JSON.stringify( body );
		}
		return fetch( url, opts ).then( function ( r ) {
			return r.json().catch( function () {
				return {};
			} ).then( function ( data ) {
				return { status: r.status, data: data || {} };
			} );
		} );
	}

	function setup( form ) {
		var t = {};
		try {
			t = JSON.parse( form.getAttribute( 'data-order-i18n' ) || '{}' );
		} catch ( e ) {}
		var rest = form.getAttribute( 'data-rest' );
		var productId = parseInt( form.getAttribute( 'data-product' ), 10 );
		var formId = parseInt( form.getAttribute( 'data-form-id' ), 10 );
		var summary = form.querySelector( '.ds-order__summary' );
		var message = form.querySelector( '.ds-order__message' );
		var button = form.querySelector( '.ds-submit' );
		var token = '';
		var key = randomKey();
		var expected = null;
		var busy = false;
		var timer = null;
		var quoteSeq = 0;

		function fieldValue( map ) {
			var wrap = form.querySelector( '.ds-field[data-maps-to="' + map + '"]' );
			if ( ! wrap || wrap.hidden ) {
				return '';
			}
			var values = window.DecoStoreForms.getValues( form );
			return values[ wrap.getAttribute( 'data-key' ) ] || '';
		}

		function control( name ) {
			var el = form.querySelector( '[name="' + name + '"]:checked' ) || form.querySelector( 'select[name="' + name + '"], input[name="' + name + '"]:not([type=radio])' );
			return el ? el.value : '';
		}

		function selection() {
			var qtyField = fieldValue( 'quantity' );
			return {
				product_id: productId,
				variation_id: parseInt( control( 'ds_variation' ) || '0', 10 ) || 0,
				qty: parseInt( qtyField || control( 'ds_qty' ) || '1', 10 ) || 1,
				wilaya_id: parseInt( fieldValue( 'wilaya' ) || '0', 10 ) || 0,
				commune_id: parseInt( fieldValue( 'commune' ) || '0', 10 ) || 0,
				method: fieldValue( 'delivery_method' ),
				offer_id: parseInt( control( 'ds_offer' ) || '0', 10 ) || 0
			};
		}

		function say( text ) {
			message.textContent = text || '';
			message.hidden = ! text;
		}

		function row( list, label, value, cls ) {
			var dt = document.createElement( 'dt' );
			var dd = document.createElement( 'dd' );
			dt.textContent = label;
			dd.textContent = value;
			if ( cls ) {
				dt.className = cls;
				dd.className = cls;
			}
			list.appendChild( dt );
			list.appendChild( dd );
		}

		function showQuote( q ) {
			if ( ! q || q.error ) {
				if ( q && 'choose_variation' === q.error ) {
					summary.textContent = t.choose_variation;
				}
				expected = null;
				return;
			}
			var list = document.createElement( 'dl' );
			list.className = 'ds-order__lines';
			row( list, t.unit, ( q.unit !== q.unit_final ? q.unit + ' → ' : '' ) + q.unit_final + ' × ' + q.qty );
			if ( q.discount ) {
				row( list, t.discount, '− ' + q.discount, 'ds-order__saving' );
			}
			( q.gifts || [] ).forEach( function ( g ) {
				row( list, t.gift, g.name + ' × ' + g.qty, 'ds-order__gift' );
			} );
			var d = { priced: q.delivery, free: t.free, not_served: t.delivery_none, unknown: t.delivery_unknown };
			row( list, t.delivery, d[ q.delivery_state ] || '' );
			row( list, t.total, q.total, 'ds-order__total' );
			summary.textContent = '';
			summary.appendChild( list );
			expected = 'priced' === q.delivery_state || 'free' === q.delivery_state ? q.total_minor : null;
		}

		function refresh() {
			var sel = selection();
			var seq = ++quoteSeq;
			return json( rest + 'quote', 'POST', sel ).then( function ( res ) {
				if ( seq === quoteSeq ) {
					showQuote( res.data );
				}
				return res;
			} ).catch( function () {} );
		}

		function loadToken() {
			return json( rest + 'token?product=' + productId + '&form=' + formId, 'GET' ).then( function ( res ) {
				if ( 200 === res.status && res.data.token ) {
					token = res.data.token;
				} else {
					say( t.unavailable );
					button.disabled = true;
				}
			} ).catch( function () {
				say( t.network );
			} );
		}

		form.addEventListener( 'change', function () {
			clearTimeout( timer );
			timer = setTimeout( refresh, 250 );
		} );
		form.addEventListener( 'input', function ( ev ) {
			if ( ev.target && 'ds_qty' === ev.target.name ) {
				clearTimeout( timer );
				timer = setTimeout( refresh, 400 );
			}
		} );

		function send( attempt ) {
			var sel = selection();
			var body = {
				token: token,
				idempotency_key: key,
				product_id: productId,
				variation_id: sel.variation_id,
				qty: sel.qty,
				offer_id: sel.offer_id,
				fields: window.DecoStoreForms.getValues( form ),
				hp: ( form.querySelector( '[name="ds_hp"]' ) || {} ).value || ''
			};
			if ( null !== expected ) {
				body.expected_total = expected;
			}
			return json( rest + 'orders', 'POST', body ).then( function ( res ) {
				var d = res.data;
				if ( ( 201 === res.status || 200 === res.status ) && d.redirect ) {
					window.location.assign( d.redirect );
					return;
				}
				busy = false;
				button.disabled = false;
				button.removeAttribute( 'aria-busy' );
				if ( 409 === res.status && 'in_progress' === d.error && attempt < 3 ) {
					busy = true;
					button.disabled = true;
					setTimeout( function () {
						send( attempt + 1 );
					}, 1500 );
					return;
				}
				if ( 422 === res.status && d.fields ) {
					say( '' );
					window.DecoStoreForms.setErrors( form, d.fields );
					return;
				}
				if ( 409 === res.status && ( 'price_changed' === d.error || 'offer_changed' === d.error ) ) {
					if ( 'offer_changed' === d.error ) {
						var none = form.querySelector( '[name="ds_offer"][value="0"]' );
						if ( none ) {
							none.checked = true;
						}
					}
					showQuote( d.quote );
					say( t[ d.error ] );
					return;
				}
				if ( 400 === res.status && 'too_fast' === d.error && attempt < 2 ) {
					// Sent within two seconds of opening the page (bot check): wait and send again.
					busy = true;
					button.disabled = true;
					button.setAttribute( 'aria-busy', 'true' );
					setTimeout( function () {
						send( attempt + 1 );
					}, 2100 );
					return;
				}
				if ( 400 === res.status && ( 'expired' === d.error || 'bad_token' === d.error ) ) {
					loadToken().then( refresh );
					say( t.expired );
					return;
				}
				if ( 'out_of_stock' === d.error ) {
					say( t.out_of_stock );
				} else if ( 'choose_variation' === d.error ) {
					say( t.choose_variation );
				} else if ( 429 === res.status ) {
					say( t.too_many );
				} else if ( 500 === res.status ) {
					say( t.server + ' ' + ( d.ref || '' ) );
				} else if ( 'product_unavailable' === d.error || 'variation_unavailable' === d.error ) {
					say( t.unavailable );
				} else {
					say( t.rejected );
				}
			} ).catch( function () {
				busy = false;
				button.disabled = false;
				button.removeAttribute( 'aria-busy' );
				say( t.network ); // Same key on the next click: the server will not create a second order.
			} );
		}

		form.addEventListener( 'ds-form:valid', function ( ev ) {
			ev.preventDefault();
			if ( busy ) {
				return;
			}
			if ( form.querySelector( '[name="ds_variation"]' ) && ! control( 'ds_variation' ) ) {
				say( t.choose_variation );
				form.querySelector( '[name="ds_variation"]' ).focus();
				return;
			}
			if ( ! token ) {
				loadToken();
				say( t.network );
				return;
			}
			busy = true;
			button.disabled = true;
			button.setAttribute( 'aria-busy', 'true' );
			say( '' );
			message.hidden = true;
			send( 0 );
		} );

		loadToken();
		refresh();
	}

	function boot() {
		Array.prototype.forEach.call( document.querySelectorAll( 'form[data-ds-order]' ), setup );
	}
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
