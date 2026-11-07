/* Live to Eat Food blocks in the editor: a live server preview, plus a setting where a block has one. No build step. */
( function ( wp ) {
	var el = wp.element.createElement;
	var SSR = wp.serverSideRender;
	var be = wp.blockEditor;
	var C = wp.components;
	var CHOICES = {
		spot: [ [ 'home', 'Home page' ], [ 'rail', 'Place pages' ], [ 'feed', 'Explore list' ], [ 'guide', 'Guide' ] ],
		form: [ [ 'woopon', 'Woo-pon' ], [ 'event', 'Event' ], [ 'job', 'Job post' ], [ 'suggest', 'Suggest a spot' ], [ 'listing', 'Fix my listing' ], [ 'special', 'Special or new dish' ], [ 'photo', 'Photo' ], [ 'contact', 'Work with Tina' ] ],
		show: [ [ 'local', 'Local deals' ], [ 'online', 'Online partner codes' ] ]
	};
	Object.keys( window.LTEF_BLOCKS || {} ).forEach( function ( name ) {
		var d = window.LTEF_BLOCKS[ name ];
		wp.blocks.registerBlockType( name, {
			apiVersion: 3,
			title: d.title,
			description: d.description,
			icon: d.icon,
			category: 'ltef',
			attributes: d.attributes,
			usesContext: d.usesContext,
			supports: { html: false, align: [ 'wide', 'full' ] },
			edit: function ( props ) {
				var controls = Object.keys( d.attributes || {} ).map( function ( key ) {
					var a = d.attributes[ key ];
					if ( CHOICES[ key ] ) {
						return el( C.SelectControl, {
							key: key, label: key === 'spot' ? 'Spot' : key === 'form' ? 'Form' : 'Show',
							value: props.attributes[ key ],
							options: CHOICES[ key ].map( function ( o ) { return { value: o[ 0 ], label: o[ 1 ] }; } ),
							onChange: function ( v ) { var o = {}; o[ key ] = v; props.setAttributes( o ); },
							__nextHasNoMarginBottom: true
						} );
					}
					if ( a.type === 'number' ) {
						return el( C.RangeControl, { key: key, label: 'How many', min: 1, max: 24, value: props.attributes[ key ], onChange: function ( v ) { var o = {}; o[ key ] = v; props.setAttributes( o ); } } );
					}
					if ( a.type === 'boolean' ) {
						return el( C.ToggleControl, { key: key, label: 'Show the ad spot', checked: !! props.attributes[ key ], onChange: function ( v ) { var o = {}; o[ key ] = v; props.setAttributes( o ); } } );
					}
					if ( a.type === 'string' ) {
						var labels = { url: 'Video link (more than one: separate with spaces)', month: 'Month (YYYY-MM)', caption: 'Tina’s caption, word for word' };
						return el( key === 'caption' ? C.TextareaControl : C.TextControl, { key: key, label: labels[ key ] || key, value: props.attributes[ key ], onChange: function ( v ) { var o = {}; o[ key ] = v; props.setAttributes( o ); }, __nextHasNoMarginBottom: true } );
					}
					return null;
				} );
				return el( 'div', be.useBlockProps(),
					controls.length ? el( be.InspectorControls, null, el( C.PanelBody, { title: 'Settings' }, controls ) ) : null,
					el( C.Disabled, null, el( SSR, { block: name, attributes: props.attributes, urlQueryArgs: props.context && props.context.postId ? { post_id: props.context.postId } : {} } ) )
				);
			},
			save: function () { return null; }
		} );
	} );
} )( window.wp );
