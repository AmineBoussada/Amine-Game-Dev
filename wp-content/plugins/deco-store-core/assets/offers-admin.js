/**
 * Offer editor: show only the sections that belong to the chosen type
 * (and, for custom rules, to the chosen action). Without JavaScript every
 * section stays visible and the server uses the fields of the chosen type.
 */
( function () {
	'use strict';
	var type = document.getElementById( 'ds-o-type' );
	var action = document.getElementById( 'ds-o-action' );
	if ( ! type ) {
		return;
	}
	function update() {
		var sections = document.querySelectorAll( '.ds-offer-section' );
		Array.prototype.forEach.call( sections, function ( s ) {
			var types = ( s.getAttribute( 'data-types' ) || '' ).split( ' ' );
			var show = types.indexOf( type.value ) !== -1;
			var only = s.getAttribute( 'data-action-types' );
			if ( show && only && 'custom_rule' === type.value && action ) {
				show = only.split( ' ' ).indexOf( action.value ) !== -1;
			}
			s.hidden = ! show;
		} );
		var value = document.getElementById( 'ds-o-action-value' );
		if ( value && action ) {
			value.disabled = [ 'free_delivery', 'add_gift' ].indexOf( action.value ) !== -1;
		}
	}
	type.addEventListener( 'change', update );
	if ( action ) {
		action.addEventListener( 'change', update );
	}
	update();
}() );
