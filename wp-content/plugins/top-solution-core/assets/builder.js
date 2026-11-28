/**
 * Top Solution form builder (admin). Edits a plain JSON state, serialised into
 * a hidden input on submit. The server re-validates everything (FormSchema);
 * this script only helps the person edit. No drag-and-drop: arrow buttons work
 * with keyboard, screen readers and touch. All text is set via textContent.
 */
( function () {
	'use strict';

	var cfg = window.tsBuilder;
	var root = document.getElementById( 'ts-builder' );
	var form = document.getElementById( 'ts-builder-form' );
	var out = document.getElementById( 'ts-fields-json' );
	if ( ! cfg || ! root || ! form || ! out ) {
		return;
	}
	var t = cfg.i18n;
	var fields = JSON.parse( JSON.stringify( cfg.fields || [] ) );
	var openKey = '';
	var statusEl = document.getElementById( 'ts-builder-status' );

	function say( msg ) {
		if ( statusEl ) {
			statusEl.textContent = '';
			window.setTimeout( function () {
				statusEl.textContent = msg;
			}, 30 );
		}
	}

	function el( tag, attrs, text ) {
		var node = document.createElement( tag );
		Object.keys( attrs || {} ).forEach( function ( k ) {
			if ( 'class' === k ) {
				node.className = attrs[ k ];
			} else {
				node.setAttribute( k, attrs[ k ] );
			}
		} );
		if ( undefined !== text ) {
			node.textContent = text;
		}
		return node;
	}

	function typeSpec( f ) {
		return cfg.types[ f.type ] || { label: f.type, input: true, options: false, mappings: [ 'meta' ], 'default': 'meta' };
	}

	function isCore( f ) {
		return cfg.core.indexOf( f.maps_to ) !== -1;
	}

	function uniqueKey( base ) {
		var key = base;
		var n = 2;
		var used = fields.map( function ( f ) {
			return f.key;
		} );
		while ( used.indexOf( key ) !== -1 ) {
			key = base + '_' + n;
			n++;
		}
		return key;
	}

	function labelFor( id, text, node ) {
		var wrap = el( 'p', { 'class': 'ts-b-row' } );
		var l = el( 'label', { 'for': id }, text );
		wrap.appendChild( l );
		wrap.appendChild( node );
		return wrap;
	}

	function textInput( f, prop, id, type ) {
		var i = el( 'input', { type: type || 'text', id: id, 'class': 'regular-text' } );
		i.value = null === f[ prop ] || undefined === f[ prop ] ? '' : f[ prop ];
		i.addEventListener( 'input', function () {
			f[ prop ] = i.value;
		} );
		return i;
	}

	function optionsText( f ) {
		return ( f.options || [] ).map( function ( o ) {
			return o.value + '|' + o.label;
		} ).join( '\n' );
	}

	function parseOptions( text ) {
		return text.split( /\r\n|\r|\n/ ).map( function ( line ) {
			return line.trim();
		} ).filter( Boolean ).map( function ( line ) {
			var i = line.indexOf( '|' );
			return -1 === i ? { label: line } : { value: line.slice( 0, i ), label: line.slice( i + 1 ) };
		} );
	}

	function renderRules( f, idx, panel ) {
		var box = el( 'fieldset', { 'class': 'ts-b-rules' } );
		box.appendChild( el( 'legend', {}, t.visibility ) );
		var core = isCore( f );
		var mode = el( 'select', { id: 'ts-vis-' + idx, 'aria-label': t.visibility } );
		[ [ 'always', t.always ], [ 'conditional', t.conditional ] ].forEach( function ( o ) {
			var opt = el( 'option', { value: o[ 0 ] }, o[ 1 ] );
			mode.appendChild( opt );
		} );
		mode.value = f.visibility && 'conditional' === f.visibility.mode ? 'conditional' : 'always';
		mode.disabled = core;
		mode.addEventListener( 'change', function () {
			f.visibility = { mode: mode.value, rules: ( f.visibility && f.visibility.rules ) || [] };
			if ( 'conditional' === mode.value && 0 === f.visibility.rules.length ) {
				addRule( f );
			}
			render();
		} );
		box.appendChild( mode );
		if ( core ) {
			return box;
		}
		if ( f.visibility && 'conditional' === f.visibility.mode ) {
			box.appendChild( el( 'p', { 'class': 'description' }, t.allRules ) );
			( f.visibility.rules || [] ).forEach( function ( rule, ri ) {
				var row = el( 'div', { 'class': 'ts-b-rule' } );
				var target = el( 'select', { 'aria-label': t.field } );
				fields.forEach( function ( other ) {
					if ( other.key === f.key || 'info' === other.type ) {
						return;
					}
					target.appendChild( el( 'option', { value: other.key }, other.label || other.key ) );
				} );
				target.value = rule.field;
				target.addEventListener( 'change', function () {
					rule.field = target.value;
				} );
				var op = el( 'select', { 'aria-label': t.operator } );
				Object.keys( cfg.ops ).forEach( function ( k ) {
					op.appendChild( el( 'option', { value: k }, cfg.ops[ k ] ) );
				} );
				op.value = rule.op;
				var val = el( 'input', { type: 'text', 'aria-label': t.value } );
				val.value = rule.value || '';
				val.addEventListener( 'input', function () {
					rule.value = val.value;
				} );
				function toggleVal() {
					val.hidden = 'empty' === op.value || 'not_empty' === op.value;
				}
				op.addEventListener( 'change', function () {
					rule.op = op.value;
					toggleVal();
				} );
				toggleVal();
				var del = el( 'button', { type: 'button', 'class': 'button button-small' }, t.removeRule );
				del.addEventListener( 'click', function () {
					f.visibility.rules.splice( ri, 1 );
					if ( 0 === f.visibility.rules.length ) {
						f.visibility.mode = 'always';
					}
					render();
				} );
				row.appendChild( target );
				row.appendChild( op );
				row.appendChild( val );
				row.appendChild( del );
				box.appendChild( row );
			} );
			var add = el( 'button', { type: 'button', 'class': 'button button-small' }, t.addRule );
			add.addEventListener( 'click', function () {
				addRule( f );
				render();
			} );
			box.appendChild( add );
		}
		return box;
	}

	function addRule( f ) {
		var first = fields.filter( function ( o ) {
			return o.key !== f.key && 'info' !== o.type;
		} )[ 0 ];
		f.visibility = f.visibility || { mode: 'conditional', rules: [] };
		f.visibility.mode = 'conditional';
		f.visibility.rules = f.visibility.rules || [];
		f.visibility.rules.push( { field: first ? first.key : '', op: 'eq', value: '' } );
	}

	function renderPanel( f, idx ) {
		var spec = typeSpec( f );
		var panel = el( 'div', { 'class': 'ts-b-panel' } );
		var core = isCore( f );
		var p = 'ts-f' + idx + '-';
		if ( 'info' === f.type ) {
			var ta = el( 'textarea', { id: p + 'text', rows: '3', 'class': 'large-text' } );
			ta.value = f.text || '';
			ta.addEventListener( 'input', function () {
				f.text = ta.value;
			} );
			panel.appendChild( labelFor( p + 'text', t.text, ta ) );
		} else {
			panel.appendChild( labelFor( p + 'label', t.label, textInput( f, 'label', p + 'label' ) ) );
			var lab = panel.querySelector( 'input' );
			lab.addEventListener( 'input', function () {
				var title = root.querySelectorAll( '.ts-b-title' )[ idx ];
				if ( title ) {
					title.textContent = lab.value || t.untitled;
				}
			} );
			if ( 'checkbox' !== f.type && 'terms' !== f.type ) {
				panel.appendChild( labelFor( p + 'ph', t.placeholder, textInput( f, 'placeholder', p + 'ph' ) ) );
			}
			panel.appendChild( labelFor( p + 'help', t.help, textInput( f, 'help', p + 'help' ) ) );
		}
		var key = textInput( f, 'key', p + 'key' );
		key.setAttribute( 'dir', 'ltr' );
		key.setAttribute( 'pattern', '[a-z][a-z0-9_]{0,39}' );
		key.setAttribute( 'aria-describedby', p + 'keyhelp' );
		panel.appendChild( labelFor( p + 'key', t.key, key ) );
		panel.appendChild( el( 'p', { 'class': 'description', id: p + 'keyhelp' }, t.keyHelp ) );
		key.addEventListener( 'change', function () {
			// Keep rules pointing at the renamed field.
			var old = key.getAttribute( 'data-old' ) || '';
			if ( old && old !== f.key ) {
				fields.forEach( function ( o ) {
					( ( o.visibility && o.visibility.rules ) || [] ).forEach( function ( r ) {
						if ( r.field === old ) {
							r.field = f.key;
						}
					} );
				} );
			}
			key.setAttribute( 'data-old', f.key );
		} );
		key.setAttribute( 'data-old', f.key );

		if ( spec.input ) {
			var req = el( 'input', { type: 'checkbox', id: p + 'req' } );
			req.checked = !! f.required || 'terms' === f.type || core;
			req.disabled = core || 'terms' === f.type;
			req.addEventListener( 'change', function () {
				f.required = req.checked;
			} );
			var rwrap = el( 'p', { 'class': 'ts-b-row' } );
			rwrap.appendChild( req );
			rwrap.appendChild( el( 'label', { 'for': p + 'req' }, ' ' + t.required ) );
			panel.appendChild( rwrap );
		}

		var width = el( 'select', { id: p + 'w' } );
		width.appendChild( el( 'option', { value: 'full' }, t.full ) );
		width.appendChild( el( 'option', { value: 'half' }, t.half ) );
		width.value = 'half' === f.width ? 'half' : 'full';
		width.addEventListener( 'change', function () {
			f.width = width.value;
		} );
		panel.appendChild( labelFor( p + 'w', t.width, width ) );

		if ( spec.mappings.length > 1 && ! core ) {
			var maps = el( 'select', { id: p + 'map' } );
			spec.mappings.forEach( function ( m ) {
				maps.appendChild( el( 'option', { value: m }, cfg.mappings[ m ] || m ) );
			} );
			maps.value = f.maps_to;
			maps.addEventListener( 'change', function () {
				f.maps_to = maps.value;
			} );
			panel.appendChild( labelFor( p + 'map', t.maps, maps ) );
		}

		if ( 'text' === f.type || 'textarea' === f.type || 'number' === f.type || 'quantity' === f.type ) {
			panel.appendChild( labelFor( p + 'min', t.min, textInput( f, 'min', p + 'min', 'number' ) ) );
			panel.appendChild( labelFor( p + 'max', t.max, textInput( f, 'max', p + 'max', 'number' ) ) );
		}

		if ( spec.options ) {
			var op = el( 'textarea', { id: p + 'opts', rows: '5', 'class': 'large-text', dir: 'auto' } );
			op.value = optionsText( f );
			op.addEventListener( 'input', function () {
				f.options = parseOptions( op.value );
			} );
			panel.appendChild( labelFor( p + 'opts', t.options, op ) );
		}

		panel.appendChild( renderRules( f, idx, panel ) );
		return panel;
	}

	function move( idx, dir ) {
		var to = idx + dir;
		if ( to < 0 || to >= fields.length ) {
			return;
		}
		var tmp = fields[ idx ];
		fields[ idx ] = fields[ to ];
		fields[ to ] = tmp;
		render( fields[ to ].key, dir < 0 ? 'up' : 'down' );
		say( t.moved );
	}

	function render( focusKey, focusBtn ) {
		root.textContent = '';
		var list = el( 'ol', { 'class': 'ts-b-list' } );
		fields.forEach( function ( f, idx ) {
			var li = el( 'li', { 'class': 'ts-b-item' + ( openKey === f.key ? ' is-open' : '' ), 'data-key': f.key } );
			var head = el( 'div', { 'class': 'ts-b-head' } );
			head.appendChild( el( 'span', { 'class': 'ts-b-type' }, typeSpec( f ).label || f.type ) );
			head.appendChild( el( 'strong', { 'class': 'ts-b-title' }, f.label || ( 'info' === f.type ? ( f.text || '' ).slice( 0, 40 ) : '' ) || t.untitled ) );
			if ( f.required || isCore( f ) ) {
				head.appendChild( el( 'span', { 'class': 'ts-badge ts-badge--ok' }, t.requiredBadge ) );
			}
			if ( f.visibility && 'conditional' === f.visibility.mode ) {
				head.appendChild( el( 'span', { 'class': 'ts-badge ts-badge--warn' }, t.conditionalBadge ) );
			}
			if ( isCore( f ) ) {
				head.appendChild( el( 'span', { 'class': 'ts-badge', title: t.locked }, t.coreBadge ) );
			}
			var tools = el( 'span', { 'class': 'ts-b-tools' } );
			var up = el( 'button', { type: 'button', 'class': 'button button-small', 'data-act': 'up', 'aria-label': t.up + ': ' + ( f.label || f.key ) }, '↑' );
			var down = el( 'button', { type: 'button', 'class': 'button button-small', 'data-act': 'down', 'aria-label': t.down + ': ' + ( f.label || f.key ) }, '↓' );
			up.disabled = 0 === idx;
			down.disabled = idx === fields.length - 1;
			up.addEventListener( 'click', function () {
				move( idx, -1 );
			} );
			down.addEventListener( 'click', function () {
				move( idx, 1 );
			} );
			var edit = el( 'button', { type: 'button', 'class': 'button button-small', 'data-act': 'edit', 'aria-expanded': openKey === f.key ? 'true' : 'false' }, openKey === f.key ? t.close : t.edit );
			edit.addEventListener( 'click', function () {
				openKey = openKey === f.key ? '' : f.key;
				render( f.key, 'edit' );
			} );
			tools.appendChild( up );
			tools.appendChild( down );
			tools.appendChild( edit );
			if ( ! isCore( f ) ) {
				var del = el( 'button', { type: 'button', 'class': 'button button-small button-link-delete', 'data-act': 'del' }, t.remove );
				del.addEventListener( 'click', function () {
					fields.splice( idx, 1 );
					if ( openKey === f.key ) {
						openKey = '';
					}
					render();
					say( t.removed );
				} );
				tools.appendChild( del );
			}
			head.appendChild( tools );
			li.appendChild( head );
			if ( openKey === f.key ) {
				li.appendChild( renderPanel( f, idx ) );
			}
			list.appendChild( li );
		} );
		root.appendChild( list );
		if ( focusKey ) {
			var target = root.querySelector( '[data-key="' + focusKey + '"] [data-act="' + ( focusBtn || 'edit' ) + '"]' );
			if ( target && ! target.disabled ) {
				target.focus();
			} else if ( target ) {
				var alt = root.querySelector( '[data-key="' + focusKey + '"] [data-act="edit"]' );
				if ( alt ) {
					alt.focus();
				}
			}
		}
	}

	var addBtn = document.getElementById( 'ts-add-field' );
	var addType = document.getElementById( 'ts-add-type' );
	if ( addBtn && addType ) {
		addBtn.addEventListener( 'click', function () {
			var type = addType.value;
			var spec = cfg.types[ type ];
			if ( ! spec ) {
				return;
			}
			var f = {
				key: uniqueKey( type ),
				type: type,
				label: 'info' === type ? '' : spec.label,
				placeholder: '',
				help: '',
				required: 'terms' === type,
				width: 'full',
				maps_to: spec[ 'default' ],
				options: spec.options ? [ { value: 'o1', label: '1' }, { value: 'o2', label: '2' } ] : [],
				min: null,
				max: null,
				text: 'info' === type ? spec.label : '',
				visibility: { mode: 'always', rules: [] }
			};
			fields.push( f );
			openKey = f.key;
			render( f.key, 'edit' );
			say( t.added );
		} );
	}

	form.addEventListener( 'submit', function () {
		out.value = JSON.stringify( fields );
	} );

	render();
}() );
