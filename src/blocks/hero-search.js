/**
 * Hero search block editor.
 */
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl, SelectControl, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import Preview from './Preview';
import ProUpsell from './ProUpsell';

registerBlockType('ssc/hero-search', {
    title: __('StaySuite Hero Search', 'staysuite-companion'),
    icon: 'cover-image',
    category: 'widgets',
    attributes: {
        title: { type: 'string', default: '' },
        subtitle: { type: 'string', default: '' },
        image_id: { type: 'number', default: 0 },
        show_search: { type: 'boolean', default: true },
        search_mode: { type: 'string', default: 'theme' },
    },
    edit({ attributes, setAttributes }) {
        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Hero settings', 'staysuite-companion')}>
                        <TextControl
                            label={__('Heading', 'staysuite-companion')}
                            value={attributes.title}
                            onChange={(title) => setAttributes({ title })}
                        />
                        <TextControl
                            label={__('Subheading', 'staysuite-companion')}
                            value={attributes.subtitle}
                            onChange={(subtitle) => setAttributes({ subtitle })}
                        />
                        <MediaUploadCheck>
                            <MediaUpload
                                allowedTypes={['image']}
                                value={attributes.image_id}
                                onSelect={(media) => setAttributes({ image_id: media.id })}
                                render={({ open }) => (
                                    <Button variant="secondary" onClick={open}>
                                        {attributes.image_id
                                            ? __('Change cover image', 'staysuite-companion')
                                            : __('Pick cover image', 'staysuite-companion')}
                                    </Button>
                                )}
                            />
                        </MediaUploadCheck>
                        <ToggleControl
                            label={__('Show search form', 'staysuite-companion')}
                            checked={attributes.show_search}
                            onChange={(show_search) => setAttributes({ show_search })}
                        />
                        <SelectControl
                            label={__('Search type', 'staysuite-companion')}
                            value={attributes.search_mode}
                            options={[
                                { label: __('Theme search (with Group pill)', 'staysuite-companion'), value: 'theme' },
                                { label: __('Simple search', 'staysuite-companion'), value: 'simple' },
                                { label: __('No search', 'staysuite-companion'), value: 'none' },
                            ]}
                            onChange={(search_mode) => setAttributes({ search_mode })}
                        />
                    </PanelBody>
                    <ProUpsell features={[__('AI concierge search', 'staysuite-companion'), __('Scheduled cover variants', 'staysuite-companion')]} />
                </InspectorControls>
                <Preview block="ssc/hero-search" attributes={attributes} />
            </>
        );
    },
    save: () => null,
});
