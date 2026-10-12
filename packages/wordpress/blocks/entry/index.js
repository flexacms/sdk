/* Flexa entry block (editor). Plain JS on purpose: no build step needed. */
(function (wp) {
    const { createElement: el, useEffect, useState } = wp.element;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, Placeholder, SelectControl, Spinner, TextControl } =
        wp.components;
    const { __ } = wp.i18n;
    const ServerSideRender = wp.serverSideRender;

    let cachedEntities = null;

    function useEntities() {
        const [state, setState] = useState({
            entities: cachedEntities,
            error: null,
        });

        useEffect(() => {
            if (cachedEntities) {
                return;
            }

            wp.apiFetch({ path: '/flexa/v1/entities' })
                .then((entities) => {
                    cachedEntities = entities;
                    setState({ entities, error: null });
                })
                .catch((error) =>
                    setState({ entities: [], error: error.message }),
                );
        }, []);

        return state;
    }

    wp.blocks.registerBlockType('flexa/entry', {
        edit({ attributes, setAttributes }) {
            const blockProps = useBlockProps();
            const { entities, error } = useEntities();
            const entity = (entities || []).find(
                (item) => item.slug === attributes.entity,
            );
            const fields = entity ? entity.fields : [];
            const entityPicker = el(SelectControl, {
                label: __('Content model', 'flexa'),
                value: attributes.entity,
                options: [
                    {
                        label: __('Select a content model…', 'flexa'),
                        value: '',
                    },
                    ...(entities || []).map((item) => ({
                        label: item.name,
                        value: item.slug,
                    })),
                ],
                onChange: (value) =>
                    setAttributes({ entity: value, field: '', image: '' }),
            });

            const inspector = el(
                InspectorControls,
                null,
                el(
                    PanelBody,
                    { title: __('Entry', 'flexa') },
                    entityPicker,
                    el(TextControl, {
                        label: __('Entry slug', 'flexa'),
                        value: attributes.slug,
                        onChange: (value) => setAttributes({ slug: value }),
                    }),
                    el(TextControl, {
                        label: __(
                            'Read the slug from the URL parameter',
                            'flexa',
                        ),
                        value: attributes.slugParam,
                        placeholder: 'entry',
                        help: __(
                            'For detail pages: /post/?entry=hello-world shows that entry. Overrides the slug when present.',
                            'flexa',
                        ),
                        onChange: (value) =>
                            setAttributes({ slugParam: value }),
                    }),
                    el(SelectControl, {
                        label: __('Show', 'flexa'),
                        value: attributes.field,
                        options: [
                            {
                                label: __('The whole entry', 'flexa'),
                                value: '',
                            },
                            ...fields.map((field) => ({
                                label: field.label,
                                value: field.key,
                            })),
                        ],
                        onChange: (value) => setAttributes({ field: value }),
                    }),
                    !attributes.field &&
                        el(SelectControl, {
                            label: __('Main image', 'flexa'),
                            value: attributes.image,
                            options: [
                                { label: __('None', 'flexa'), value: '' },
                                ...fields
                                    .filter((field) => field.type === 'image')
                                    .map((field) => ({
                                        label: field.label,
                                        value: field.key,
                                    })),
                            ],
                            onChange: (value) =>
                                setAttributes({ image: value }),
                        }),
                ),
            );

            const ready =
                attributes.entity && (attributes.slug || attributes.slugParam);
            const body = ready
                ? el(ServerSideRender, { block: 'flexa/entry', attributes })
                : el(
                      Placeholder,
                      {
                          icon: 'media-document',
                          label: __('Flexa entry', 'flexa'),
                          instructions:
                              error ||
                              __(
                                  'Choose a content model and an entry in the block settings.',
                                  'flexa',
                              ),
                      },
                      entities ? entityPicker : el(Spinner),
                  );

            return el('div', blockProps, inspector, body);
        },
        save: () => null,
    });
})(window.wp);
