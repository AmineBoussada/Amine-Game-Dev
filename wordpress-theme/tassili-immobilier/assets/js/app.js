/**
 * Tassili Immobilier: behaviour shared by every page.
 * Mobile drawer, light/dark theme, favourites, toast messages, header shadow.
 * Exposes window.Tassili for the page scripts.
 */
( function () {
	'use strict';

	var data = window.TassiliData || { i18n: {} };
	var t = data.i18n || {};

	function $( selector, root ) {
		return ( root || document ).querySelector( selector );
	}
	function $$( selector, root ) {
		return Array.prototype.slice.call( ( root || document ).querySelectorAll( selector ) );
	}
	function debounce( fn, wait ) {
		var timer;
		return function () {
			var args = arguments;
			var self = this;
			clearTimeout( timer );
			timer = setTimeout( function () {
				fn.apply( self, args );
			}, wait );
		};
	}
	function sprintf( template, values ) {
		var i = 0;
		return String( template ).replace( /%(\d+\$)?[sd]/g, function ( match, position ) {
			var index = position ? parseInt( position, 10 ) - 1 : i++;
			return values[ index ] !== undefined ? values[ index ] : match;
		} );
	}
	var store = {
		get: function ( key, fallback ) {
			try {
				var value = localStorage.getItem( key );
				return value === null ? fallback : JSON.parse( value );
			} catch ( e ) {
				return fallback;
			}
		},
		set: function ( key, value ) {
			try {
				localStorage.setItem( key, JSON.stringify( value ) );
			} catch ( e ) { /* private mode: keep working without storage */ }
		}
	};

	/* Digits grouped for display: narrow no-break space in French, dots in Arabic. */
	function formatNumber( n ) {
		var sep = data.lang === 'ar' ? '.' : ' ';
		return String( Math.round( Number( n ) || 0 ) ).replace( /\B(?=(\d{3})+(?!\d))/g, sep );
	}
	function digits( value ) {
		var d = String( value || '' ).replace( /\D/g, '' );
		return d ? parseInt( d, 10 ) : 0;
	}
	function bindMoneyInput( input ) {
		if ( ! input || input.dataset.money ) {
			return;
		}
		input.dataset.money = '1';
		input.addEventListener( 'input', function () {
			var n = digits( input.value );
			input.value = n ? formatNumber( n ) : '';
		} );
	}

	/* ---------- Toast ---------- */
	var toastTimer;
	function toast( message ) {
		var region = $( '#toast-region' );
		if ( ! region ) {
			return;
		}
		region.textContent = '';
		var el = document.createElement( 'div' );
		el.className = 'toast';
		el.textContent = message;
		region.appendChild( el );
		clearTimeout( toastTimer );
		toastTimer = setTimeout( function () {
			if ( el.parentNode ) {
				el.parentNode.removeChild( el );
			}
		}, 2600 );
	}

	/* ---------- Favourites (kept in this browser only) ---------- */
	var FAV_KEY = 'tassili-favs';
	var favs = {
		list: function () {
			var v = store.get( FAV_KEY, [] );
			return Array.isArray( v ) ? v.map( Number ).filter( Boolean ) : [];
		},
		has: function ( id ) {
			return favs.list().indexOf( Number( id ) ) > -1;
		},
		toggle: function ( id ) {
			id = Number( id );
			var list = favs.list();
			var at = list.indexOf( id );
			var on = at < 0;
			if ( on ) {
				list.push( id );
			} else {
				list.splice( at, 1 );
			}
			store.set( FAV_KEY, list );
			document.dispatchEvent( new CustomEvent( 'tassili:favs', { detail: { id: id, on: on } } ) );
			return on;
		}
	};

	function syncFavs( root ) {
		$$( '[data-fav]', root || document ).forEach( function ( button ) {
			var on = favs.has( button.getAttribute( 'data-fav' ) );
			button.setAttribute( 'aria-pressed', String( on ) );
			button.setAttribute( 'aria-label', on ? t.favRemove : t.favAdd );
		} );
		var n = favs.list().length;
		$$( '[data-fav-count]' ).forEach( function ( el ) {
			el.textContent = n;
			el.hidden = ! n;
		} );
		$$( '[data-fav-link]' ).forEach( function ( el ) {
			el.setAttribute( 'aria-label', n ? sprintf( t.favoritesN, [ n ] ) : t.favorites );
		} );
	}

	/* ---------- Theme ---------- */
	function isDark() {
		var explicit = document.documentElement.getAttribute( 'data-theme' );
		if ( explicit ) {
			return explicit === 'dark';
		}
		return !! ( window.matchMedia && window.matchMedia( '(prefers-color-scheme: dark)' ).matches );
	}
	function syncTheme() {
		var dark = isDark();
		$$( '[data-theme-toggle]' ).forEach( function ( button ) {
			button.setAttribute( 'aria-label', dark ? t.themeLight : t.themeDark );
			var moon = $( '[data-theme-icon="moon"]', button );
			var sun = $( '[data-theme-icon="sun"]', button );
			if ( moon ) {
				moon.hidden = dark;
			}
			if ( sun ) {
				sun.hidden = ! dark;
			}
		} );
	}

	/* ---------- Drawer ---------- */
	var drawerOpener = null;
	function drawer() {
		return $( '#drawer' );
	}
	function openDrawer() {
		var d = drawer();
		if ( ! d ) {
			return;
		}
		drawerOpener = document.activeElement;
		d.hidden = false;
		document.body.classList.add( 'no-scroll' );
		$$( '[data-drawer-open]' ).forEach( function ( b ) {
			b.setAttribute( 'aria-expanded', 'true' );
			b.setAttribute( 'aria-label', t.menuClose );
		} );
		var first = $( '.drawer-panel a, .drawer-panel button', d );
		if ( first ) {
			first.focus();
		}
	}
	function closeDrawer() {
		var d = drawer();
		if ( ! d || d.hidden ) {
			return;
		}
		d.hidden = true;
		document.body.classList.remove( 'no-scroll' );
		$$( '[data-drawer-open]' ).forEach( function ( b ) {
			b.setAttribute( 'aria-expanded', 'false' );
			b.setAttribute( 'aria-label', t.menuOpen );
		} );
		if ( drawerOpener && drawerOpener.focus ) {
			drawerOpener.focus();
		}
	}

	function init() {
		document.documentElement.classList.add( 'js' );
		syncFavs();
		syncTheme();

		document.addEventListener( 'click', function ( event ) {
			var target = event.target.closest ? event.target : event.target.parentElement;
			if ( ! target ) {
				return;
			}
			if ( target.closest( '[data-drawer-open]' ) ) {
				openDrawer();
				return;
			}
			if ( target.closest( '[data-drawer-close]' ) ) {
				closeDrawer();
				return;
			}
			if ( target.closest( '.drawer-nav a, .drawer-foot a, .lang-btn' ) ) {
				closeDrawer();
			}
			if ( target.closest( '[data-theme-toggle]' ) ) {
				var next = isDark() ? 'light' : 'dark';
				document.documentElement.setAttribute( 'data-theme', next );
				try {
					localStorage.setItem( 'tassili-theme', next );
				} catch ( e ) { /* ignore */ }
				syncTheme();
				return;
			}
			var fav = target.closest( '[data-fav]' );
			if ( fav ) {
				event.preventDefault();
				var on = favs.toggle( fav.getAttribute( 'data-fav' ) );
				syncFavs();
				toast( on ? t.favAdded : t.favRemoved );
			}
		} );

		document.addEventListener( 'keydown', function ( event ) {
			var d = drawer();
			if ( ! d || d.hidden ) {
				return;
			}
			if ( event.key === 'Escape' ) {
				closeDrawer();
			} else if ( event.key === 'Tab' ) {
				var items = $$( 'a, button', $( '.drawer-panel', d ) );
				if ( ! items.length ) {
					return;
				}
				var first = items[ 0 ];
				var last = items[ items.length - 1 ];
				if ( event.shiftKey && document.activeElement === first ) {
					event.preventDefault();
					last.focus();
				} else if ( ! event.shiftKey && document.activeElement === last ) {
					event.preventDefault();
					first.focus();
				}
			}
		} );

		window.addEventListener( 'resize', debounce( function () {
			if ( window.innerWidth >= 1080 ) {
				closeDrawer();
			}
		}, 100 ) );

		var header = $( '#site-header' );
		if ( header ) {
			var onScroll = function () {
				header.classList.toggle( 'scrolled', window.scrollY > 40 );
			};
			window.addEventListener( 'scroll', onScroll, { passive: true } );
			onScroll();
		}

		if ( window.matchMedia ) {
			var query = window.matchMedia( '(prefers-color-scheme: dark)' );
			if ( query.addEventListener ) {
				query.addEventListener( 'change', syncTheme );
			}
		}
		window.addEventListener( 'storage', function ( event ) {
			if ( event.key === FAV_KEY ) {
				syncFavs();
			}
		} );
	}

	window.Tassili = {
		data: data,
		t: t,
		$: $,
		$$: $$,
		store: store,
		debounce: debounce,
		sprintf: sprintf,
		toast: toast,
		favs: favs,
		syncFavs: syncFavs,
		formatNumber: formatNumber,
		digits: digits,
		bindMoneyInput: bindMoneyInput,
		closeDrawer: closeDrawer
	};

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
