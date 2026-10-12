/* Flexa entries block (editor). Plain JS on purpose: no build step needed. */
( function ( wp ) {
	const { createElement: el, useEffect, useState } = wp.element;
	const { InspectorControls, useBlockProps } = wp.blockEditor;
	const { PanelBody, Placeholder, RangeControl, SelectControl, Spinner, TextControl } = wp.components;
	const { __ } = wp.i18n;
	const ServerSideRender = wp.serverSideRender;

	let cachedEntities = null;

	function useEntities() {
		const [ state, setState ] = useState( { entities: cachedEntities, error: null } );

		useEffect( () => {
			if ( cachedEntities ) {
				return;
			}

			wp.apiFetch( { path: '/flexa/v1/entities' } )
				.then( ( entities ) => {
					cachedEntities = entities;
					setState( { entities, error: null } );
				} )
				.catch( ( error ) => setState( { entities: [], error: error.message } ) );
		}, [] );

		return state;
	}

	function fieldOptions( entity, types, emptyLabel ) {
		const fields = entity ? entity.fields.filter( ( field ) => ! types || types.includes( field.type ) ) : [];

		return [ { label: emptyLabel, value: '' }, ...fields.map( ( field ) => ( { label: field.label, value: field.key } ) ) ];
	}

	wp.blocks.registerBlockType( 'flexa/entries', {
		edit( { attributes, setAttributes } ) {
			const blockProps = useBlockProps();
			const { entities, error } = useEntities();
			const entity = ( entities || [] ).find( ( item ) => item.slug === attributes.entity );
			const entityOptions = [
				{ label: __( 'Select a content model…', 'flexa' ), value: '' },
				...( entities || [] ).map( ( item ) => ( { label: item.name, value: item.slug } ) ),
			];
			const entityPicker = el( SelectControl, {
				label: __( 'Content model', 'flexa' ),
				value: attributes.entity,
				options: entityOptions,
				onChange: ( value ) => setAttributes( { entity: value, image: '', excerpt: '' } ),
			} );

			const inspector = el(
				InspectorControls,
				null,
				el(
					PanelBody,
					{ title: __( 'Content', 'flexa' ) },
					entityPicker,
					el( RangeControl, {
						label: __( 'Number of entries', 'flexa' ),
						value: attributes.perPage,
						min: 1,
						max: 24,
						onChange: ( value ) => setAttributes( { perPage: value || 6 } ),
					} ),
					el( SelectControl, {
						label: __( 'Image field', 'flexa' ),
						value: attributes.image,
						options: fieldOptions( entity, [ 'image' ], __( 'No image', 'flexa' ) ),
						onChange: ( value ) => setAttributes( { image: value } ),
					} ),
					el( SelectControl, {
						label: __( 'Text field', 'flexa' ),
						value: attributes.excerpt,
						options: fieldOptions( entity, [ 'text', 'textarea', 'richtext' ], __( 'Title only', 'flexa' ) ),
						onChange: ( value ) => setAttributes( { excerpt: value } ),
					} ),
					el( TextControl, {
						label: __( 'Link', 'flexa' ),
						value: attributes.link,
						placeholder: '/blog/?entry={slug}',
						help: __( 'Where each entry links to. Use {slug} or {id}.', 'flexa' ),
						onChange: ( value ) => setAttributes( { link: value } ),
					} )
				),
				el(
					PanelBody,
					{ title: __( 'Layout', 'flexa' ) },
					el( SelectControl, {
						label: __( 'Layout', 'flexa' ),
						value: attributes.layout,
						options: [
							{ label: __( 'Grid', 'flexa' ), value: 'grid' },
							{ label: __( 'List', 'flexa' ), value: 'list' },
						],
						onChange: ( value ) => setAttributes( { layout: value } ),
					} ),
					attributes.layout === 'grid' &&
						el( RangeControl, {
							label: __( 'Columns', 'flexa' ),
							value: attributes.columns,
							min: 1,
							max: 6,
							onChange: ( value ) => setAttributes( { columns: value || 3 } ),
						} )
				)
			);

			const body = attributes.entity
				? el( ServerSideRender, { block: 'flexa/entries', attributes } )
				: el(
						Placeholder,
						{
							icon: 'grid-view',
							label: __( 'Flexa entries', 'flexa' ),
							instructions: error || __( 'Choose the content model to list.', 'flexa' ),
						},
						entities ? entityPicker : el( Spinner )
				  );

			return el( 'div', blockProps, inspector, body );
		},
		save: () => null,
	} );
} )( window.wp );
