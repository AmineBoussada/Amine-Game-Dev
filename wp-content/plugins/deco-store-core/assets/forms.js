/**
 * Déco Store Core — front-end form behaviour (vanilla JS, no dependencies).
 *
 * Mirrors the server rules for usability only: conditional visibility,
 * dependent selects (wilaya > commune > pickup office), inline validation and
 * an error summary. The PHP SubmissionValidator is always the authority.
 *
 * Public API: window.DecoStoreForms.getValues( formElement ),
 *             window.DecoStoreForms.setErrors( formElement, { key: message } )
 * Event:      "ds-form:valid" (cancelable) with detail.values, fired on the
 *             form when the checks pass. In preview mode nothing is sent.
 */
( function () {
	'use strict';

	var cache = {};

	function $( root, sel ) {
		return root.querySelector( sel );
	}

	function all( root, sel ) {
		return Array.prototype.slice.call( root.querySelectorAll( sel ) );
	}

	/* ------------------------------------------------------------ helpers */
	function westernDigits( text ) {
		return String( text ).replace( /[٠-٩۰-۹]/g, function ( ch ) {
			var code = ch.charCodeAt( 0 );
			return String( code >= 0x06F0 ? code - 0x06F0 : code - 0x0660 );
		} );
	}

	function controls( wrapper ) {
		return all( wrapper, 'input, select, textarea' );
	}

	function rawValue( wrapper ) {
		var type = wrapper.getAttribute( 'data-type' );
		var els = controls( wrapper );
		if ( ! els.length ) {
			return '';
		}
		if ( 'delivery_method' === type || 'radio' === type ) {
			var checked = els.filter( function ( el ) {
				return el.checked;
			} )[ 0 ];
			return checked ? checked.value : '';
		}
		if ( 'checkbox' === type || 'terms' === type ) {
			return els[ 0 ].checked ? '1' : '';
		}
		return String( els[ 0 ].value ).trim();
	}

	/* --------------------------------------------------------- visibility */
	function testRule( op, actual, expected ) {
		switch ( op ) {
			case 'eq':
				return actual === expected;
			case 'neq':
				return actual !== expected;
			case 'in':
				return expected.split( ',' ).map( function ( s ) {
					return s.trim();
				} ).indexOf( actual ) !== -1;
			case 'empty':
				return '' === actual;
			case 'not_empty':
				return '' !== actual;
		}
		return false;
	}

	function isVisible( state, key, memo ) {
		if ( Object.prototype.hasOwnProperty.call( memo, key ) ) {
			return memo[ key ];
		}
		memo[ key ] = true;
		var rules = state.rules[ key ];
		if ( ! rules ) {
			return true;
		}
		for ( var i = 0; i < rules.length; i++ ) {
			var ref = rules[ i ].field;
			if ( ! state.fields[ ref ] ) {
				memo[ key ] = false;
				return false;
			}
			var actual = isVisible( state, ref, memo ) ? rawValue( state.fields[ ref ] ) : '';
			if ( ! testRule( rules[ i ].op, actual, rules[ i ].value || '' ) ) {
				memo[ key ] = false;
				return false;
			}
		}
		memo[ key ] = true;
		return true;
	}

	function applyVisibility( state ) {
		var memo = {};
		Object.keys( state.fields ).forEach( function ( key ) {
			var wrapper = state.fields[ key ];
			var visible = isVisible( state, key, memo );
			var type = wrapper.getAttribute( 'data-type' );
			// Commune stays hidden when its wilaya has no commune list.
			if ( 'commune' === type && state.noCommunes ) {
				visible = false;
			}
			wrapper.hidden = ! visible;
			controls( wrapper ).forEach( function ( el ) {
				if ( ! visible ) {
					el.disabled = true;
				} else if ( 'commune' !== type && 'pickup_point' !== type ) {
					el.disabled = false;
				}
			} );
		} );
		// Commune / pickup selects are enabled by their own loaders.
		syncDependentEnabled( state );
	}

	function syncDependentEnabled( state ) {
		var commune = state.byMap.commune;
		var pickup = state.byMap.pickup_point;
		var wilayaVal = state.byMap.wilaya ? rawValue( state.byMap.wilaya ) : '';
		[ commune, pickup ].forEach( function ( wrapper ) {
			if ( ! wrapper || wrapper.hidden ) {
				return;
			}
			var select = $( wrapper, 'select' );
			select.disabled = ! wilayaVal || select.options.length <= 1 && ! select.getAttribute( 'data-loaded' );
		} );
	}

	/* ------------------------------------------------------ dependent data */
	function fetchJson( url ) {
		if ( cache[ url ] ) {
			return cache[ url ];
		}
		cache[ url ] = fetch( url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' } ).then( function ( res ) {
			if ( ! res.ok ) {
				throw new Error( 'http ' + res.status );
			}
			return res.json();
		} ).catch( function ( err ) {
			delete cache[ url ];
			throw err;
		} );
		return cache[ url ];
	}

	function fillSelect( select, items, placeholder, labelKey ) {
		select.innerHTML = '';
		var first = document.createElement( 'option' );
		first.value = '';
		first.textContent = placeholder;
		select.appendChild( first );
		items.forEach( function ( item ) {
			var opt = document.createElement( 'option' );
			opt.value = String( item.id );
			opt.textContent = labelKey ? item[ labelKey ] + ( item.carrier ? ' (' + item.carrier + ')' : '' ) : item.name;
			select.appendChild( opt );
		} );
		// "Loaded" only when there is something to choose.
		if ( items.length ) {
			select.setAttribute( 'data-loaded', '1' );
		} else {
			select.removeAttribute( 'data-loaded' );
		}
	}

	function setHelp( wrapper, text ) {
		var help = $( wrapper, '.ds-help.ds-dyn' );
		if ( ! text ) {
			if ( help ) {
				help.remove();
			}
			return;
		}
		if ( ! help ) {
			help = document.createElement( 'p' );
			help.className = 'ds-help ds-dyn';
			help.setAttribute( 'role', 'status' );
			wrapper.appendChild( help );
		}
		help.textContent = text;
	}

	function loadCommunes( state ) {
		var wrapper = state.byMap.commune;
		var wilayaWrapper = state.byMap.wilaya;
		if ( ! wrapper || ! wilayaWrapper ) {
			return Promise.resolve();
		}
		var select = $( wrapper, 'select' );
		var wilaya = rawValue( wilayaWrapper );
		state.noCommunes = false;
		select.removeAttribute( 'data-loaded' );
		if ( ! wilaya ) {
			fillSelect( select, [], state.i18n.choose_wilaya );
			select.removeAttribute( 'data-loaded' );
			select.disabled = true;
			return Promise.resolve();
		}
		var token = ( state.communeToken = ( state.communeToken || 0 ) + 1 );
		fillSelect( select, [], state.i18n.loading );
		select.removeAttribute( 'data-loaded' );
		select.disabled = true;
		return fetchJson( state.rest + 'communes?wilaya=' + encodeURIComponent( wilaya ) ).then( function ( items ) {
			if ( token !== state.communeToken ) {
				return;
			}
			state.noCommunes = 0 === items.length;
			fillSelect( select, items, state.i18n.choose );
			select.disabled = false;
			applyVisibility( state );
		} ).catch( function () {
			if ( token !== state.communeToken ) {
				return;
			}
			fillSelect( select, [], state.i18n.load_failed );
			select.removeAttribute( 'data-loaded' );
			select.disabled = true;
		} );
	}

	function loadPickups( state ) {
		var wrapper = state.byMap.pickup_point;
		if ( ! wrapper || wrapper.hidden ) {
			return Promise.resolve();
		}
		var select = $( wrapper, 'select' );
		var wilaya = state.byMap.wilaya ? rawValue( state.byMap.wilaya ) : '';
		var commune = state.byMap.commune && ! state.byMap.commune.hidden ? rawValue( state.byMap.commune ) : '';
		var key = wilaya + '|' + commune;
		if ( ! wilaya ) {
			fillSelect( select, [], state.i18n.choose_wilaya );
			select.removeAttribute( 'data-loaded' );
			select.disabled = true;
			return Promise.resolve();
		}
		if ( state.pickupKey === key && select.getAttribute( 'data-loaded' ) ) {
			return Promise.resolve();
		}
		state.pickupKey = key;
		var token = ( state.pickupToken = ( state.pickupToken || 0 ) + 1 );
		select.disabled = true;
		return fetchJson( state.rest + 'pickups?wilaya=' + encodeURIComponent( wilaya ) + ( commune ? '&commune=' + encodeURIComponent( commune ) : '' ) ).then( function ( items ) {
			if ( token !== state.pickupToken ) {
				return;
			}
			fillSelect( select, items, state.i18n.choose, 'name' );
			select.disabled = 0 === items.length;
			setHelp( wrapper, 0 === items.length ? state.i18n.no_pickup : '' );
		} ).catch( function () {
			if ( token === state.pickupToken ) {
				setHelp( wrapper, state.i18n.load_failed );
			}
		} );
	}

	/* ---------------------------------------------------------- validation */
	function fieldLabel( wrapper ) {
		var label = $( wrapper, '.ds-label' ) || $( wrapper, 'legend' );
		if ( ! label ) {
			return wrapper.getAttribute( 'data-key' );
		}
		return label.textContent.replace( /\*/g, '' ).replace( /\(.*?\)\s*$/, '' ).trim();
	}

	function checkField( state, wrapper ) {
		var type = wrapper.getAttribute( 'data-type' );
		if ( 'info' === type ) {
			return null;
		}
		var required = '1' === wrapper.getAttribute( 'data-required' );
		var value = rawValue( wrapper );
		var min = wrapper.hasAttribute( 'data-min' ) ? parseInt( wrapper.getAttribute( 'data-min' ), 10 ) : null;
		var max = wrapper.hasAttribute( 'data-max' ) ? parseInt( wrapper.getAttribute( 'data-max' ), 10 ) : null;

		if ( 'checkbox' === type || 'terms' === type ) {
			return required && '1' !== value ? 'required' : null;
		}
		if ( '' === value ) {
			if ( 'commune' === type && state.noCommunes ) {
				return null;
			}
			return required || 'quantity' === type ? 'required' : null;
		}
		if ( 'phone' === type ) {
			var cleaned = westernDigits( value ).replace( /[\s\-.()\/]+/g, '' );
			if ( state.phonePrefix ) {
				cleaned = cleaned.replace( new RegExp( state.phonePrefix ), '0' );
			}
			return new RegExp( state.phonePattern ).test( cleaned ) ? null : 'phone';
		}
		if ( 'text' === type || 'textarea' === type ) {
			if ( null !== min && value.length < min ) {
				return 'too_short';
			}
			if ( null !== max && value.length > max ) {
				return 'too_long';
			}
			return null;
		}
		if ( 'number' === type || 'quantity' === type ) {
			var digits = westernDigits( value );
			if ( ! /^\d{1,9}$/.test( digits ) ) {
				return 'invalid';
			}
			var n = parseInt( digits, 10 );
			if ( 'quantity' === type && n < 1 ) {
				return 'invalid';
			}
			if ( null !== min && n < min ) {
				return 'too_small';
			}
			if ( null !== max && n > max ) {
				return 'too_large';
			}
		}
		return null;
	}

	function showError( wrapper, message ) {
		var err = $( wrapper, '.ds-error' );
		var els = controls( wrapper );
		els.forEach( function ( el ) {
			if ( message ) {
				el.setAttribute( 'aria-invalid', 'true' );
			} else {
				el.removeAttribute( 'aria-invalid' );
			}
		} );
		if ( err ) {
			err.textContent = message || '';
			err.hidden = ! message;
		}
	}

	function validate( state, focusFirst ) {
		var problems = [];
		Object.keys( state.fields ).forEach( function ( key ) {
			var wrapper = state.fields[ key ];
			if ( wrapper.hidden ) {
				showError( wrapper, '' );
				return;
			}
			var code = checkField( state, wrapper );
			showError( wrapper, code ? state.i18n[ code ] || state.i18n.invalid : '' );
			if ( code ) {
				problems.push( { key: key, wrapper: wrapper, message: state.i18n[ code ] || state.i18n.invalid } );
			}
		} );
		renderSummary( state, problems, focusFirst );
		return 0 === problems.length;
	}

	function renderSummary( state, problems, focusFirst ) {
		var box = $( state.form, '.ds-errors' );
		box.innerHTML = '';
		if ( problems.length ) {
			var title = document.createElement( 'p' );
			title.textContent = state.i18n.summary;
			var list = document.createElement( 'ul' );
			problems.forEach( function ( p ) {
				var li = document.createElement( 'li' );
				var link = document.createElement( 'a' );
				var target = controls( p.wrapper )[ 0 ];
				link.href = '#' + ( target ? target.id : '' );
				link.textContent = fieldLabel( p.wrapper ) + ': ' + p.message;
				link.addEventListener( 'click', function ( ev ) {
					ev.preventDefault();
					if ( target ) {
						target.focus();
					}
				} );
				li.appendChild( link );
				list.appendChild( li );
			} );
			box.appendChild( title );
			box.appendChild( list );
			box.hidden = false;
			if ( focusFirst ) {
				var first = controls( problems[ 0 ].wrapper )[ 0 ];
				box.focus();
				if ( first ) {
					first.focus();
				}
			}
		} else {
			box.hidden = true;
		}
	}

	/**
	 * Keep the error list in step with the fields: once the list is shown,
	 * it lists only the errors still visible (and disappears when none is
	 * left). It never adds errors while the customer is still filling in.
	 */
	function refreshSummary( state ) {
		var box = $( state.form, '.ds-errors' );
		if ( ! box || box.hidden ) {
			return;
		}
		var problems = [];
		Object.keys( state.fields ).forEach( function ( key ) {
			var wrapper = state.fields[ key ];
			var err = $( wrapper, '.ds-error:not([hidden])' );
			if ( ! wrapper.hidden && err && err.textContent ) {
				problems.push( { key: key, wrapper: wrapper, message: err.textContent } );
			}
		} );
		renderSummary( state, problems, false );
	}

	/**
	 * Show errors returned by the server ({ fieldKey: message }).
	 */
	function setErrors( form, errors ) {
		var state = form.__dsState;
		if ( ! state ) {
			return;
		}
		var problems = [];
		Object.keys( errors || {} ).forEach( function ( key ) {
			var wrapper = state.fields[ key ];
			if ( wrapper ) {
				showError( wrapper, errors[ key ] );
				problems.push( { key: key, wrapper: wrapper, message: errors[ key ] } );
			}
		} );
		renderSummary( state, problems, true );
	}

	/* ---------------------------------------------------------------- init */
	function getValues( form ) {
		var values = {};
		all( form, '.ds-field[data-key]' ).forEach( function ( wrapper ) {
			if ( wrapper.hidden || 'info' === wrapper.getAttribute( 'data-type' ) ) {
				return;
			}
			var v = rawValue( wrapper );
			if ( '' !== v ) {
				values[ wrapper.getAttribute( 'data-key' ) ] = v;
			}
		} );
		return values;
	}

	function init( form ) {
		if ( form.getAttribute( 'data-ds-ready' ) ) {
			return;
		}
		form.setAttribute( 'data-ds-ready', '1' );
		var state = {
			form: form,
			fields: {},
			rules: {},
			byMap: {},
			rest: form.getAttribute( 'data-rest' ),
			phonePattern: form.getAttribute( 'data-phone-pattern' ),
			phonePrefix: form.getAttribute( 'data-phone-prefix' ),
			i18n: {},
			noCommunes: false
		};
		form.__dsState = state;
		try {
			state.i18n = JSON.parse( form.getAttribute( 'data-i18n' ) || '{}' );
		} catch ( e ) {
			state.i18n = {};
		}
		all( form, '.ds-field[data-key]' ).forEach( function ( wrapper ) {
			var key = wrapper.getAttribute( 'data-key' );
			state.fields[ key ] = wrapper;
			var map = wrapper.getAttribute( 'data-maps-to' );
			if ( map ) {
				state.byMap[ map ] = wrapper;
			}
			var rules = wrapper.getAttribute( 'data-visibility' );
			if ( rules ) {
				try {
					state.rules[ key ] = JSON.parse( rules );
				} catch ( e ) {
					state.rules[ key ] = [ { field: '', op: 'eq', value: '' } ]; // Unknown rule: stay hidden.
				}
			}
		} );

		applyVisibility( state );
		loadCommunes( state );

		form.addEventListener( 'change', function ( ev ) {
			var wrapper = ev.target.closest( '.ds-field[data-key]' );
			var map = wrapper ? wrapper.getAttribute( 'data-maps-to' ) : '';
			applyVisibility( state );
			if ( 'wilaya' === map ) {
				state.pickupKey = null;
				loadCommunes( state ).then( function () {
					loadPickups( state );
				} );
			} else {
				loadPickups( state );
			}
			if ( wrapper && ! wrapper.hidden && $( wrapper, '.ds-error:not([hidden])' ) ) {
				var code = checkField( state, wrapper );
				showError( wrapper, code ? state.i18n[ code ] || state.i18n.invalid : '' );
			}
			refreshSummary( state );
		} );

		// Remove an error as soon as the typed value becomes valid, while typing:
		// waiting for the field to lose focus would move the page under the
		// customer's finger exactly when they tap the next field.
		form.addEventListener( 'input', function ( ev ) {
			var wrapper = ev.target.closest( '.ds-field[data-key]' );
			if ( wrapper && ! wrapper.hidden && $( wrapper, '.ds-error:not([hidden])' ) && ! checkField( state, wrapper ) ) {
				showError( wrapper, '' );
				refreshSummary( state );
			}
		} );

		form.addEventListener( 'submit', function ( ev ) {
			ev.preventDefault();
			var note = $( form, '.ds-preview-note' );
			note.hidden = true;
			if ( ! validate( state, true ) ) {
				return;
			}
			var detail = { values: getValues( form ), formId: form.getAttribute( 'data-form-id' ) };
			var proceed = form.dispatchEvent( new CustomEvent( 'ds-form:valid', { cancelable: true, bubbles: true, detail: detail } ) );
			if ( proceed && form.hasAttribute( 'data-preview' ) ) {
				note.textContent = state.i18n.preview_done;
				note.hidden = false;
			}
		} );
	}

	function boot() {
		all( document, '[data-ds-form]' ).forEach( init );
	}

	window.DecoStoreForms = { getValues: getValues, setErrors: setErrors, init: boot };
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
