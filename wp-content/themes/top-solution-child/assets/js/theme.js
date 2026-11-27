/**
 * Top Solution Child — navigation and header behaviour (vanilla JS, no deps).
 *
 * - Mobile drawer: open/close, focus trap, Escape, overlay click, body lock.
 * - Header search row toggle.
 * - Submenu toggle buttons (desktop dropdowns + drawer accordions).
 * - Mobile header hides while scrolling down, shows when scrolling up.
 */
( function () {
	'use strict';

	var doc = document;
	var html = doc.documentElement;
	var desktopQuery = window.matchMedia( '(min-width: 1024px)' );
	var reducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	var FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select, textarea, [tabindex]:not([tabindex="-1"])';

	/* ---------------------------------------------------------------- Drawer */
	var drawer = doc.querySelector( '[data-ts-drawer]' );
	var openButtons = doc.querySelectorAll( '[data-ts-drawer-open]' );
	var lastFocused = null;

	function drawerPanel() {
		return drawer ? drawer.querySelector( '.ts-drawer__panel' ) : null;
	}

	function openDrawer() {
		if ( ! drawer ) {
			return;
		}
		lastFocused = doc.activeElement;
		drawer.hidden = false;
		// Force a reflow so the transition runs.
		void drawer.offsetWidth;
		drawer.classList.add( 'is-open' );
		html.classList.add( 'ts-no-scroll' );
		openButtons.forEach( function ( btn ) {
			btn.setAttribute( 'aria-expanded', 'true' );
		} );
		var panel = drawerPanel();
		var closeBtn = panel ? panel.querySelector( '.ts-drawer__head button' ) : null;
		( closeBtn || panel ).focus();
	}

	function closeDrawer( restoreFocus ) {
		if ( ! drawer || drawer.hidden ) {
			return;
		}
		drawer.classList.remove( 'is-open' );
		html.classList.remove( 'ts-no-scroll' );
		openButtons.forEach( function ( btn ) {
			btn.setAttribute( 'aria-expanded', 'false' );
		} );
		var done = function () {
			drawer.hidden = true;
		};
		if ( reducedMotion.matches ) {
			done();
		} else {
			window.setTimeout( done, 250 );
		}
		if ( false !== restoreFocus && lastFocused && lastFocused.focus ) {
			lastFocused.focus();
		}
	}

	openButtons.forEach( function ( btn ) {
		btn.addEventListener( 'click', openDrawer );
	} );

	if ( drawer ) {
		drawer.addEventListener( 'click', function ( event ) {
			if ( event.target.closest( '[data-ts-drawer-close]' ) ) {
				closeDrawer();
				return;
			}
			var link = event.target.closest( '.ts-drawer__nav a' );
			if ( link && '#' !== link.getAttribute( 'href' ) ) {
				closeDrawer( false );
			}
		} );

		drawer.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key ) {
				event.preventDefault();
				closeDrawer();
				return;
			}
			if ( 'Tab' !== event.key ) {
				return;
			}
			var items = Array.prototype.filter.call(
				drawerPanel().querySelectorAll( FOCUSABLE ),
				function ( el ) {
					return null !== el.offsetParent;
				}
			);
			if ( ! items.length ) {
				return;
			}
			var first = items[ 0 ];
			var last = items[ items.length - 1 ];
			if ( event.shiftKey && doc.activeElement === first ) {
				event.preventDefault();
				last.focus();
			} else if ( ! event.shiftKey && doc.activeElement === last ) {
				event.preventDefault();
				first.focus();
			}
		} );
	}

	desktopQuery.addEventListener( 'change', function ( event ) {
		if ( event.matches ) {
			closeDrawer( false );
		}
	} );

	/* ---------------------------------------------------------------- Search */
	var searchToggle = doc.querySelector( '[data-ts-search-toggle]' );
	var searchRow = doc.getElementById( 'ts-search-row' );

	if ( searchToggle && searchRow ) {
		searchToggle.addEventListener( 'click', function () {
			var open = searchRow.hidden;
			searchRow.hidden = ! open;
			searchToggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			if ( open ) {
				var input = searchRow.querySelector( 'input[type="search"]' );
				if ( input ) {
					input.focus();
				}
			}
		} );
		searchRow.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key ) {
				searchRow.hidden = true;
				searchToggle.setAttribute( 'aria-expanded', 'false' );
				searchToggle.focus();
			}
		} );
	}

	/* -------------------------------------------------------------- Submenus */
	var chevron = '<svg class="ts-icon" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M6 9l6 6 6-6"/></svg>';

	function setupSubmenus( root ) {
		root.querySelectorAll( '.menu-item-has-children' ).forEach( function ( item ) {
			var link = item.querySelector( ':scope > a' );
			var sub = item.querySelector( ':scope > .sub-menu' );
			if ( ! link || ! sub || item.querySelector( ':scope > .ts-submenu-toggle' ) ) {
				return;
			}
			var button = doc.createElement( 'button' );
			button.type = 'button';
			button.className = 'ts-submenu-toggle';
			button.setAttribute( 'aria-expanded', 'false' );
			button.innerHTML = chevron + '<span class="screen-reader-text"></span>';
			button.querySelector( '.screen-reader-text' ).textContent = ( link.textContent || '' ).trim();
			link.after( button );
			button.addEventListener( 'click', function () {
				var open = 'true' !== button.getAttribute( 'aria-expanded' );
				button.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
				item.classList.toggle( 'is-open', open );
			} );
		} );
	}

	doc.querySelectorAll( '.ts-nav, .ts-drawer__nav' ).forEach( setupSubmenus );

	// Close desktop dropdowns on outside click / Escape.
	doc.addEventListener( 'click', function ( event ) {
		if ( event.target.closest( '.ts-nav' ) ) {
			return;
		}
		doc.querySelectorAll( '.ts-nav .menu-item.is-open' ).forEach( function ( item ) {
			item.classList.remove( 'is-open' );
			var btn = item.querySelector( ':scope > .ts-submenu-toggle' );
			if ( btn ) {
				btn.setAttribute( 'aria-expanded', 'false' );
			}
		} );
	} );

	/* ----------------------------------------------- Hide header on scroll down */
	var header = doc.querySelector( '[data-ts-header]' );
	var lastY = window.pageYOffset;
	var ticking = false;

	function onScroll() {
		var y = window.pageYOffset;
		if ( header && ! desktopQuery.matches && ! reducedMotion.matches ) {
			var delta = y - lastY;
			if ( y > 160 && delta > 6 ) {
				header.classList.add( 'is-hidden' );
			} else if ( delta < -6 || y <= 160 ) {
				header.classList.remove( 'is-hidden' );
			}
		} else if ( header ) {
			header.classList.remove( 'is-hidden' );
		}
		lastY = y;
		ticking = false;
	}

	if ( header ) {
		window.addEventListener(
			'scroll',
			function () {
				if ( ! ticking ) {
					ticking = true;
					window.requestAnimationFrame( onScroll );
				}
			},
			{ passive: true }
		);
		// Never hide the header while keyboard focus is inside it.
		header.addEventListener( 'focusin', function () {
			header.classList.remove( 'is-hidden' );
		} );
	}
}() );
