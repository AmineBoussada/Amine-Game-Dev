/**
 * Tassili Immobilier: single listing.
 * Photo gallery with a full-screen viewer (keyboard, swipe, right-to-left aware), "read more",
 * share, and the visit-request dialog. The first photo is already in the HTML, so the page is
 * usable before this script runs.
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

	var root = $( '#property' );
	if ( ! root ) {
		return;
	}

	/* ---------- Gallery ---------- */

	var gallery = $( '#gallery' );
	var photos = [];
	try {
		photos = JSON.parse( gallery.getAttribute( 'data-photos' ) || '[]' );
	} catch ( e ) {
		photos = [];
	}
	var index = 0;
	var swiped = false;
	var lightbox = $( '#lightbox' );

	function show( i ) {
		if ( ! photos.length ) {
			return;
		}
		index = ( i + photos.length ) % photos.length;
		var photo = photos[ index ];
		var img = $( '#g-img' );
		img.src = photo.src;
		img.alt = photo.alt || '';
		var link = $( '#g-open' );
		if ( link ) {
			link.href = photo.full || photo.src;
		}
		$( '#g-counter' ).textContent = ( index + 1 ) + ' / ' + photos.length;
		$( '#g-caption' ).textContent = photo.caption || '';
		$$( '[data-thumb]' ).forEach( function ( b ) {
			b.setAttribute( 'aria-current', String( Number( b.getAttribute( 'data-thumb' ) ) === index ) );
		} );
		if ( lightbox && lightbox.open ) {
			var big = $( '#lb-img' );
			big.src = photo.full || photo.src;
			big.alt = photo.alt || '';
			$( '#lb-counter' ).textContent = ( index + 1 ) + ' / ' + photos.length;
			$( '#lb-caption' ).textContent = photo.caption || '';
			$( '#lb-caption-top' ).textContent = photo.alt || '';
		}
	}
	function step( delta ) {
		show( index + delta );
	}
	function openLightbox() {
		if ( ! lightbox ) {
			return;
		}
		if ( lightbox.showModal ) {
			lightbox.showModal();
		} else {
			lightbox.setAttribute( 'open', '' );
		}
		show( index );
		var close = $( '[data-lb-close]', lightbox );
		if ( close ) {
			close.focus();
		}
	}
	function closeLightbox() {
		if ( lightbox && lightbox.open ) {
			lightbox.close();
		}
	}

	/* ---------- Read more ---------- */

	function clampDescription() {
		var d = $( '#p-desc' );
		var b = $( '#read-more' );
		if ( ! d || ! b ) {
			return;
		}
		d.classList.add( 'clamped' );
		var over = d.scrollHeight > d.clientHeight + 4;
		if ( ! over ) {
			d.classList.remove( 'clamped' );
		}
		b.hidden = ! over;
	}

	/* ---------- Share ---------- */

	function share( title ) {
		var url = window.location.href.split( '#' )[ 0 ];
		if ( navigator.share ) {
			navigator.share( { title: title, url: url } ).catch( function () { /* cancelled */ } );
			return;
		}
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( url ).then( function () {
				T.toast( t.shareCopied );
			}, function () {
				T.toast( t.shareFail );
			} );
		} else {
			T.toast( t.shareFail );
		}
	}

	/* ---------- Visit dialog ---------- */

	function openVisit() {
		var dialog = $( '#visit-dialog' );
		if ( ! dialog ) {
			return;
		}
		if ( dialog.showModal ) {
			dialog.showModal();
		} else {
			dialog.setAttribute( 'open', '' );
		}
		var first = $( '#v-name', dialog );
		if ( first ) {
			first.focus();
		}
	}

	/* ---------- Events ---------- */

	root.addEventListener( 'click', function ( event ) {
		var target = event.target;
		var go = target.closest( '[data-g]' );
		if ( go ) {
			step( Number( go.getAttribute( 'data-g' ) ) );
			return;
		}
		var thumb = target.closest( '[data-thumb]' );
		if ( thumb ) {
			var i = Number( thumb.getAttribute( 'data-thumb' ) );
			show( i );
			if ( i === 3 && photos.length > 4 && window.innerWidth >= 900 ) {
				openLightbox();
			}
			return;
		}
		if ( target.closest( '#g-open' ) ) {
			event.preventDefault();
			if ( swiped ) {
				swiped = false;
				return;
			}
			openLightbox();
			return;
		}
		if ( target.closest( '[data-lb-close]' ) || target === lightbox ) {
			closeLightbox();
			return;
		}
		var more = target.closest( '#read-more' );
		if ( more ) {
			var d = $( '#p-desc' );
			var open = ! d.classList.toggle( 'clamped' );
			more.setAttribute( 'aria-expanded', String( open ) );
			more.textContent = open ? t.readLess : t.readMore;
			return;
		}
		if ( target.closest( '[data-visit]' ) ) {
			openVisit();
			return;
		}
		var visitDialog = $( '#visit-dialog' );
		if ( target.closest( '[data-visit-close]' ) || target === visitDialog ) {
			if ( visitDialog.open ) {
				visitDialog.close();
			}
			return;
		}
		var shareButton = target.closest( '[data-share]' );
		if ( shareButton ) {
			share( shareButton.getAttribute( 'data-share-title' ) || document.title );
		}
	} );

	root.addEventListener( 'keydown', function ( event ) {
		var inLightbox = lightbox && lightbox.open;
		var inGallery = inLightbox || ( event.target.closest && event.target.closest( '.gallery' ) );
		if ( ! inGallery ) {
			return;
		}
		if ( event.key === 'ArrowRight' || event.key === 'ArrowLeft' ) {
			event.preventDefault();
			var forward = ( event.key === 'ArrowRight' ) !== data.rtl;
			step( forward ? 1 : -1 );
		}
	} );

	/* Swipe on the main photo and in the viewer. */
	var startX = null;
	root.addEventListener( 'pointerdown', function ( event ) {
		swiped = false;
		if ( event.target.closest( '#g-main, #lb-stage' ) ) {
			startX = event.clientX;
		}
	} );
	root.addEventListener( 'pointercancel', function () {
		startX = null;
	} );
	root.addEventListener( 'pointerup', function ( event ) {
		if ( startX === null ) {
			return;
		}
		var dx = event.clientX - startX;
		startX = null;
		if ( Math.abs( dx ) < 40 ) {
			return;
		}
		swiped = true;
		var forward = ( dx < 0 ) !== data.rtl;
		step( forward ? 1 : -1 );
	} );
	root.addEventListener( 'dragstart', function ( event ) {
		if ( event.target.closest( '.gallery, #lb-stage' ) ) {
			event.preventDefault();
		}
	} );

	show( 0 );
	clampDescription();
	window.addEventListener( 'resize', T.debounce( clampDescription, 200 ) );
}() );
