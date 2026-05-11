(function() {
    'use strict';

    var el = wp.element.createElement;
    var __ = wp.i18n.__;
    var registerBlockType = wp.blocks.registerBlockType;
    var blockEditor = wp.blockEditor || wp.editor;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = wp.components.PanelBody;
    var TextControl = wp.components.TextControl;
    var ToggleControl = wp.components.ToggleControl;
    var ServerSideRender = wp.serverSideRender || wp.components.ServerSideRender;

    registerBlockType('aniksmta/toc', {
        title: __('Anik Smart TOC', 'anik-smart-table-of-contents'),
        description: __('Insert a Smart TOC block with optional title and collapsed state.', 'anik-smart-table-of-contents'),
        icon: 'list-view',
        category: 'widgets',
        keywords: [
            __('table of contents', 'anik-smart-table-of-contents'),
            __('toc', 'anik-smart-table-of-contents'),
            __('smart toc', 'anik-smart-table-of-contents')
        ],
        attributes: {
            title: {
                type: 'string',
                default: ''
            },
            collapsed: {
                type: 'boolean',
                default: false
            }
        },
        edit: function(props) {
            var attributes = props.attributes;

            return [
                el(
                    InspectorControls,
                    { key: 'inspector' },
                    el(
                        PanelBody,
                        {
                            title: __('TOC Settings', 'anik-smart-table-of-contents'),
                            initialOpen: true
                        },
                        el(TextControl, {
                            label: __('TOC Title', 'anik-smart-table-of-contents'),
                            value: attributes.title || '',
                            onChange: function(value) {
                                props.setAttributes({ title: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: __('Collapsed by default', 'anik-smart-table-of-contents'),
                            checked: !!attributes.collapsed,
                            onChange: function(value) {
                                props.setAttributes({ collapsed: !!value });
                            }
                        })
                    )
                ),
                el(
                    'div',
                    {
                        key: 'preview',
                        className: props.className
                    },
                    ServerSideRender
                        ? el(ServerSideRender, {
                            block: 'aniksmta/toc',
                            attributes: attributes
                        })
                        : el('p', null, __('TOC preview unavailable. Save and view the post to verify output.', 'anik-smart-table-of-contents'))
                )
            ];
        },
        save: function() {
            return null;
        }
    });
})();
