/**
 * Tassili Immobilier: search.
 *  - Home page: live "View N properties" count, dependent commune list.
 *  - Results page: filters update the results in place (no reload), the URL stays shareable,
 *    pagination and filter chips work the same way, mobile filters open as a sheet.
 *  - Favourites page: loads the saved listings from the REST API.
 * Everything also works without this script: the forms are plain GET forms.
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

	/* ---------- helpers ---------- */

	function setCommunes( select, wilayaSlug, selected ) {
		var list = ( data.communes && data.communes[ wilayaSlug ] ) || [];
		select.textContent = '';
		var all = document.createElement( 'option' );
		all.value = '';
		all.textContent = t.allCommunes;
		select.appendChild( all );
		list.forEach( function ( c ) {
			var option = document.createElement( 'option' );
			option.value = c.slug;
			option.textContent = c.name;
			if ( c.slug === selected ) {
				option.selected = true;
			}
			select.appendChild( option );
		} );
		select.disabled = ! list.length;
	}

	function countLabel( n ) {
		if ( n === 0 ) {
			return t.seeNone;
		}
		if ( n === 1 ) {
			return t.seeOne;
		}
		return T.sprintf( t.seeMany, [ T.formatNumber( n ) ] );
	}

	function toQuery( params ) {
		var parts = [];
		Object.keys( params ).forEach( function ( key ) {
			var value = params[ key ];
			if ( value === '' || value === null || value === undefined || value === false || value === 0 ) {
				return;
			}
			parts.push( encodeURIComponent( key ) + '=' + encodeURIComponent( value ) );
		} );
		return parts.join( '&' );
	}

	/* Read the filter form into clean query parameters. */
	function readForm( form ) {
		var el = form.elements;
		var checked = function ( name ) {
			return $$( 'input[name="' + name + '"]:checked', form ).map( function ( i ) {
				return i.value;
			} );
		};
		var mode = ( $( 'input[name="tx"]:checked', form ) || {} ).value || '';
		return {
			tx: mode === 'vente' || mode === 'location' ? mode : '',
			neuf: mode === 'neuf' ? '1' : '',
			wilaya: el.wilaya ? el.wilaya.value : '',
			commune: el.commune ? el.commune.value : '',
			type: el.type ? el.type.value : '',
			typo: checked( 'typo[]' ).join( ',' ),
			pmin: el.pmin ? T.digits( el.pmin.value ) : 0,
			pmax: el.pmax ? T.digits( el.pmax.value ) : 0,
			smin: el.smin ? T.digits( el.smin.value ) : 0,
			papers: checked( 'papers[]' ).join( ',' ),
			amen: checked( 'amen[]' ).join( ',' )
		};
	}

	/* ============================ Home page ============================ */

	function initHome() {
		var form = $( '#home-search' );
		if ( ! form ) {
			return;
		}
		var panel = $( '#hero-search' );
		var label = $( '#hs-go-label' );
		var wilaya = $( '#hs-wilaya' );
		var commune = $( '#hs-commune' );
		var more = $( '#hs-more' );
		var request = 0;

		function params() {
			var el = form.elements;
			var mode = ( $( 'input[name="tx"]:checked', form ) || {} ).value || 'vente';
			return {
				tx: mode === 'vente' || mode === 'location' ? mode : '',
				neuf: mode === 'neuf' ? '1' : '',
				wilaya: el.wilaya.value,
				commune: el.commune.value,
				type: el.type.value,
				typo: el.typo.value,
				pmax: T.digits( el.pmax.value )
			};
		}

		function refresh() {
			var id = ++request;
			fetch( data.restUrl + 'count?' + toQuery( params() ) + '&lang=' + encodeURIComponent( data.lang ), { credentials: 'same-origin' } )
				.then( function ( r ) {
					return r.json();
				} )
				.then( function ( json ) {
					if ( id === request && typeof json.count === 'number' ) {
						label.textContent = countLabel( json.count );
					}
				} )
				.catch( function () { /* keep the last label */ } );
		}
		var refreshSoon = T.debounce( refresh, 200 );

		T.bindMoneyInput( $( '#hs-budget' ) );
		form.addEventListener( 'change', function ( event ) {
			if ( event.target === wilaya ) {
				setCommunes( commune, wilaya.value, '' );
			}
			refresh();
		} );
		form.addEventListener( 'input', function ( event ) {
			if ( event.target.name === 'pmax' ) {
				refreshSoon();
			}
		} );
		if ( more ) {
			more.addEventListener( 'click', function () {
				var open = panel.classList.toggle( 'expanded' );
				more.setAttribute( 'aria-expanded', String( open ) );
				more.textContent = open ? t.lessCriteria : t.moreCriteria;
			} );
		}
		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			var query = toQuery( params() );
			window.location.href = data.archiveUrl + ( data.archiveUrl.indexOf( '?' ) > -1 ? '&' : '?' ) + query;
		} );
		refresh();
	}

	/* ========================== Results page =========================== */

	function initArchive() {
		var root = $( '#archive' );
		var form = $( '#filter-form' );
		if ( ! root || ! form ) {
			return;
		}
		var wrap = $( '#results-wrap' );
		var filters = $( '#filters' );
		var opener = $( '#filter-open' );
		var sheetFrom = null;
		var seq = 0;
		var view = $( '#results.list' ) ? 'list' : 'grid';

		T.bindMoneyInput( $( '#ff-pmin' ) );
		T.bindMoneyInput( $( '#ff-pmax' ) );

		function currentParams() {
			var p = readForm( form );
			var sort = $( '#sort' );
			if ( sort && sort.value !== 'recent' ) {
				p.sort = sort.value;
			}
			if ( view === 'list' ) {
				p.view = 'list';
			}
			return p;
		}
		function urlFor( params ) {
			var base = data.archiveUrl;
			var q = toQuery( params );
			return base + ( q ? ( base.indexOf( '?' ) > -1 ? '&' : '?' ) + q : '' );
		}

		/* Swap the parts of the page that depend on the results. */
		function swap( doc, replaceForm ) {
			var map = [ '#a-title', '#a-count', '#filter-open-label', '#filters-apply' ];
			map.forEach( function ( selector ) {
				var fresh = $( selector, doc );
				var current = $( selector );
				if ( fresh && current ) {
					current.textContent = fresh.textContent;
				}
			} );
			var newWrap = $( '#results-wrap', doc );
			if ( newWrap && wrap ) {
				wrap.innerHTML = newWrap.innerHTML;
				T.syncFavs( wrap );
			}
			if ( replaceForm ) {
				var newForm = $( '#filter-form', doc );
				if ( newForm ) {
					form.innerHTML = newForm.innerHTML;
					T.bindMoneyInput( $( '#ff-pmin' ) );
					T.bindMoneyInput( $( '#ff-pmax' ) );
				}
				var sort = $( '#sort' );
				var newSort = $( '#sort', doc );
				if ( sort && newSort ) {
					sort.value = newSort.value;
				}
			}
			if ( $( '#results.list', doc ) ) {
				view = 'list';
			} else if ( $( '#results', doc ) ) {
				view = 'grid';
			}
			$$( '[data-view]' ).forEach( function ( b ) {
				b.setAttribute( 'aria-pressed', String( b.getAttribute( 'data-view' ) === view ) );
			} );
			if ( doc.title ) {
				document.title = doc.title;
			}
		}

		function load( url, options ) {
			options = options || {};
			var id = ++seq;
			wrap.setAttribute( 'aria-busy', 'true' );
			return fetch( url, { credentials: 'same-origin', headers: { Accept: 'text/html' } } )
				.then( function ( r ) {
					if ( ! r.ok ) {
						throw new Error( 'bad status' );
					}
					return r.text();
				} )
				.then( function ( html ) {
					if ( id !== seq ) {
						return;
					}
					var doc = new DOMParser().parseFromString( html, 'text/html' );
					if ( ! $( '#results-wrap', doc ) ) {
						throw new Error( 'unexpected page' );
					}
					swap( doc, !! options.replaceForm );
					if ( options.push === 'replace' ) {
						history.replaceState( null, '', url );
					} else if ( options.push !== false ) {
						history.pushState( null, '', url );
					}
					wrap.removeAttribute( 'aria-busy' );
					if ( options.scroll ) {
						var col = $( '.results-col', root );
						if ( col ) {
							col.scrollIntoView( { block: 'start' } );
						}
					}
				} )
				.catch( function () {
					window.location.href = url;
				} );
		}

		function fromForm( push ) {
			load( urlFor( currentParams() ), { push: push || true } );
		}
		var fromFormSoon = T.debounce( function () {
			fromForm( 'replace' );
		}, 350 );

		form.addEventListener( 'change', function ( event ) {
			if ( event.target.name === 'wilaya' ) {
				setCommunes( $( '#ff-commune' ), event.target.value, '' );
			}
			if ( /^(pmin|pmax|smin)$/.test( event.target.name ) ) {
				return; // text fields update while typing, see the input listener
			}
			fromForm();
		} );
		form.addEventListener( 'input', function ( event ) {
			if ( /^(pmin|pmax|smin)$/.test( event.target.name ) ) {
				fromFormSoon();
			}
		} );
		root.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			closeSheet();
			fromForm();
		} );
		var sort = $( '#sort' );
		if ( sort ) {
			sort.addEventListener( 'change', function () {
				fromForm();
			} );
		}

		root.addEventListener( 'click', function ( event ) {
			var target = event.target;
			var viewButton = target.closest( '[data-view]' );
			if ( viewButton ) {
				event.preventDefault();
				view = viewButton.getAttribute( 'data-view' );
				fromForm();
				return;
			}
			var link = target.closest( 'a.page-numbers, a.active-chip, a.clear-all, a[data-reset]' );
			if ( link && link.href ) {
				event.preventDefault();
				var isPage = link.classList.contains( 'page-numbers' );
				var reset = link.hasAttribute( 'data-reset' );
				var next = link.href;
				if ( isPage ) {
					/* Page links come from the server and may drop our view choice. */
					var u = new URL( next, window.location.href );
					if ( view === 'list' && ! u.searchParams.has( 'view' ) ) {
						u.searchParams.set( 'view', 'list' );
					}
					next = u.toString();
				}
				load( next, { replaceForm: true, scroll: isPage } ).then( function () {
					if ( isPage ) {
						var current = $( '.pagination .current', root );
						if ( current && current.focus ) {
							current.setAttribute( 'tabindex', '-1' );
							current.focus();
						}
					} else if ( reset ) {
						closeSheet();
					}
				} );
				return;
			}
			if ( target.closest( '#filter-open' ) ) {
				openSheet();
				return;
			}
			if ( target.closest( '[data-sheet-close]' ) ) {
				closeSheet();
			}
		} );

		window.addEventListener( 'popstate', function () {
			load( window.location.href, { replaceForm: true, push: false } );
		} );

		/* Mobile filter sheet */
		function openSheet() {
			if ( ! filters ) {
				return;
			}
			sheetFrom = document.activeElement;
			filters.classList.add( 'open' );
			document.body.classList.add( 'no-scroll' );
			opener.setAttribute( 'aria-expanded', 'true' );
			var close = $( '.filters-close', filters );
			if ( close ) {
				close.focus();
			}
		}
		function closeSheet() {
			if ( ! filters || ! filters.classList.contains( 'open' ) ) {
				return;
			}
			filters.classList.remove( 'open' );
			document.body.classList.remove( 'no-scroll' );
			opener.setAttribute( 'aria-expanded', 'false' );
			if ( sheetFrom && sheetFrom.focus ) {
				sheetFrom.focus();
			}
		}
		document.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'Escape' ) {
				closeSheet();
			}
		} );
	}

	/* ========================= Favourites page ========================= */

	function initFavorites() {
		var box = $( '#favorites' );
		if ( ! box ) {
			return;
		}
		var grid = $( '#favorites-grid' );
		var empty = $( '#favorites-empty' );
		var count = $( '#fav-count' );

		function show( n ) {
			grid.hidden = ! n;
			empty.hidden = !! n;
			count.textContent = n ? T.sprintf( t.favoritesN, [ n ] ) : '';
		}

		function load() {
			var ids = T.favs.list();
			if ( ! ids.length ) {
				grid.textContent = '';
				show( 0 );
				return;
			}
			fetch( data.restUrl + 'cards?ids=' + ids.join( ',' ) + '&lang=' + encodeURIComponent( data.lang ), { credentials: 'same-origin' } )
				.then( function ( r ) {
					return r.json();
				} )
				.then( function ( json ) {
					grid.innerHTML = json.html || '';
					T.syncFavs( grid );
					show( json.count || 0 );
				} )
				.catch( function () {
					empty.hidden = false;
				} );
		}

		document.addEventListener( 'tassili:favs', function ( event ) {
			if ( event.detail && ! event.detail.on ) {
				var card = $( '.card[data-id="' + event.detail.id + '"]', grid );
				if ( card ) {
					card.remove();
				}
				show( $$( '.card', grid ).length );
			}
		} );
		load();
	}

	function init() {
		initHome();
		initArchive();
		initFavorites();
	}
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
