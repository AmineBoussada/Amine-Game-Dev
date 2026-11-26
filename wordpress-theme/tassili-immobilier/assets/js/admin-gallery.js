/* Gallery picker for the property edit screen (WordPress media modal). */
( function ( $ ) {
	'use strict';

	var $list = $( '#tassili-gallery-list' );
	var $input = $( '#tassili-gallery-ids' );
	var $add = $( '#tassili-gallery-add' );
	var frame = null;

	if ( ! $list.length || ! $input.length || ! window.wp || ! wp.media ) {
		return;
	}

	function sync() {
		var ids = $list.children( 'li' ).map( function () {
			return $( this ).data( 'id' );
		} ).get();
		$input.val( ids.join( ',' ) );
	}

	function addItem( attachment ) {
		var sizes = attachment.sizes || {};
		var src = ( sizes.thumbnail || sizes.full || attachment ).url;
		if ( $list.children( 'li[data-id="' + attachment.id + '"]' ).length ) {
			return;
		}
		var $li = $( '<li />' ).attr( 'data-id', attachment.id ).data( 'id', attachment.id );
		$( '<img />' ).attr( { src: src, alt: '' } ).appendTo( $li );
		$( '<button type="button" class="button-link tassili-gallery__remove" />' )
			.attr( 'aria-label', $add.data( 'remove' ) || 'Remove' )
			.html( '&times;' )
			.appendTo( $li );
		$list.append( $li );
	}

	$add.on( 'click', function ( event ) {
		event.preventDefault();
		if ( ! frame ) {
			frame = wp.media( {
				title: $add.data( 'title' ),
				button: { text: $add.data( 'button' ) },
				library: { type: 'image' },
				multiple: 'add'
			} );
			frame.on( 'select', function () {
				frame.state().get( 'selection' ).each( function ( model ) {
					addItem( model.toJSON() );
				} );
				sync();
			} );
		}
		frame.open();
	} );

	$list.on( 'click', '.tassili-gallery__remove', function ( event ) {
		event.preventDefault();
		$( this ).closest( 'li' ).remove();
		sync();
	} );

	$list.sortable( { update: sync, tolerance: 'pointer' } );
}( jQuery ) );
