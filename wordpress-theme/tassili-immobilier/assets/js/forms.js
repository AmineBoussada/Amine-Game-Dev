/**
 * Tassili Immobilier: contact and visit-request forms.
 * Validates in the browser for instant feedback, then posts to admin-ajax.php. The server
 * repeats every check (nonce, honeypot, rate limit, validation), so this is a convenience,
 * not a guard. Without JavaScript the forms post to admin-post.php and redirect back.
 */
( function () {
	'use strict';

	var T = window.Tassili;
	if ( ! T ) {
		return;
	}
	var $ = T.$;
	var $$ = T.$$;
	var t = T.t;
	var data = T.data;

	var PHONE = /^(?:\+213|00213|0)[567]\d{8}$/;
	var EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

	function localDate( value ) {
		var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec( value );
		return m ? new Date( +m[ 1 ], +m[ 2 ] - 1, +m[ 3 ] ) : null;
	}
	function tomorrow() {
		var d = new Date();
		d.setHours( 0, 0, 0, 0 );
		d.setDate( d.getDate() + 1 );
		return d;
	}

	/* Returns an error message, or '' when the field is fine. */
	function check( el ) {
		var rule = el.getAttribute( 'data-rule' );
		var value = ( el.value || '' ).trim();
		if ( el.type === 'checkbox' ) {
			return el.required && ! el.checked ? ( rule === 'consent' ? t.errConsent : t.errRequired ) : '';
		}
		if ( el.required && ! value ) {
			return t.errRequired;
		}
		if ( ! value ) {
			return '';
		}
		if ( rule === 'name' && value.replace( /[^\p{L}]/gu, '' ).length < 2 ) {
			return t.errName;
		}
		if ( rule === 'phone' && ! PHONE.test( value.replace( /[\s.\-()]/g, '' ) ) ) {
			return t.errPhone;
		}
		if ( rule === 'email' && ! EMAIL.test( value ) ) {
			return t.errEmail;
		}
		if ( rule === 'message' && value.length < 10 ) {
			return t.errMessage;
		}
		if ( rule === 'visitdate' ) {
			var d = localDate( value );
			if ( ! d || d < tomorrow() ) {
				return t.errDate;
			}
			if ( d.getDay() === Number( data.closedDay ) ) {
				return t.errClosed;
			}
		}
		return '';
	}

	function show( el, message ) {
		var err = document.getElementById( el.id + '-err' );
		if ( message ) {
			el.setAttribute( 'aria-invalid', 'true' );
			if ( err ) {
				err.textContent = message;
				err.hidden = false;
				el.setAttribute( 'aria-describedby', err.id );
			}
		} else {
			el.removeAttribute( 'aria-invalid' );
			if ( err ) {
				err.textContent = '';
				err.hidden = true;
				el.removeAttribute( 'aria-describedby' );
			}
		}
	}

	function fields( form ) {
		return $$( '[data-rule], [required]', form );
	}

	function summary( form, visible, message ) {
		var box = $( '[data-form-summary]', form );
		if ( box ) {
			box.hidden = ! visible;
			if ( message ) {
				box.textContent = message;
			}
		}
	}

	function validate( form ) {
		var first = null;
		fields( form ).forEach( function ( el ) {
			var message = check( el );
			show( el, message );
			if ( message && ! first ) {
				first = el;
			}
		} );
		summary( form, !! first, t.errSummary );
		if ( first ) {
			first.focus();
		}
		return ! first;
	}

	function post( form ) {
		var body = new FormData( form );
		body.set( 'action', form.getAttribute( 'data-ajax-action' ) );
		return fetch( data.ajaxUrl + '?lang=' + encodeURIComponent( data.lang ), {
			method: 'POST',
			body: body,
			credentials: 'same-origin'
		} ).then( function ( response ) {
			return response.json().catch( function () {
				return { success: false, data: { message: t.errSend } };
			} );
		} );
	}

	function bind( form, onSuccess ) {
		var tried = false;
		form.setAttribute( 'novalidate', '' );

		function live( event ) {
			if ( ! tried || ! event.target.matches( '[data-rule], [required]' ) ) {
				return;
			}
			show( event.target, check( event.target ) );
			summary( form, !! $( '[aria-invalid="true"]', form ) );
		}
		form.addEventListener( 'input', live );
		form.addEventListener( 'change', live );

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			tried = true;
			if ( ! validate( form ) ) {
				return;
			}
			var button = $( '[type="submit"]', form );
			var label = button ? button.innerHTML : '';
			if ( button ) {
				button.disabled = true;
				button.textContent = t.sending;
			}
			post( form ).then( function ( json ) {
				if ( button ) {
					button.disabled = false;
					button.innerHTML = label;
				}
				if ( json && json.success ) {
					tried = false;
					onSuccess( json.data || {}, form );
					return;
				}
				var info = ( json && json.data ) || {};
				var errors = info.errors || {};
				var firstBad = null;
				Object.keys( errors ).forEach( function ( name ) {
					var el = form.elements[ name ];
					if ( el && el.id ) {
						show( el, errors[ name ] );
						firstBad = firstBad || el;
					}
				} );
				summary( form, true, info.message || t.errSend );
				if ( firstBad ) {
					firstBad.focus();
				}
			} ).catch( function () {
				if ( button ) {
					button.disabled = false;
					button.innerHTML = label;
				}
				summary( form, true, t.errSend );
			} );
		} );
	}

	/* Success panel built with DOM calls, so server text is never parsed as HTML. */
	function successPanel( info, closeLabel ) {
		var panel = document.createElement( 'div' );
		panel.className = 'success-panel';
		panel.setAttribute( 'role', 'status' );
		var heading = document.createElement( 'h3' );
		heading.tabIndex = -1;
		heading.textContent = info.title || '';
		var text = document.createElement( 'p' );
		text.textContent = info.text || '';
		panel.appendChild( heading );
		panel.appendChild( text );
		if ( closeLabel ) {
			var button = document.createElement( 'button' );
			button.type = 'button';
			button.className = 'btn btn-ghost';
			button.setAttribute( 'data-visit-close', '' );
			button.textContent = closeLabel;
			panel.appendChild( button );
		}
		return panel;
	}

	function init() {
		var contact = $( '#contact-form-el' );
		if ( contact ) {
			bind( contact, function ( info ) {
				var wrap = $( '#contact-form-wrap' );
				var done = $( '#contact-success' );
				$( '#success-title' ).textContent = info.title || '';
				$( '#success-text' ).textContent = info.text || '';
				wrap.hidden = true;
				done.hidden = false;
				$( '#success-title' ).focus();
			} );
			var again = $( '#success-again' );
			if ( again ) {
				again.addEventListener( 'click', function ( event ) {
					event.preventDefault();
					contact.reset();
					fields( contact ).forEach( function ( el ) {
						show( el, '' );
					} );
					summary( contact, false );
					$( '#contact-success' ).hidden = true;
					$( '#contact-form-wrap' ).hidden = false;
					contact.elements.name.focus();
				} );
			}
		}

		var visit = $( '#visit-form' );
		if ( visit ) {
			bind( visit, function ( info ) {
				var body = $( '#visit-body' );
				body.textContent = '';
				var panel = successPanel( info, t.close || '×' );
				body.appendChild( panel );
				$( 'h3', panel ).focus();
			} );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
