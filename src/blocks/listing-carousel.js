/**
 * Listing carousel block editor.
 */
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, RangeControl, ToggleControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import Preview from './Preview';
import ProUpsell from './ProUpsell';

registerBlockType('ssc/listing-carousel', {
    title: __('StaySuite Listing Carousel', 'staysuite-companion'),
    icon: 'slides',
    category: 'widgets',
    attributes: {
        title: { type: 'string', default: '' },
        source: { type: 'string', default: 'rooms' },
        taxonomy: { type: 'string', default: '' },
        term: { type: 'string', default: '' },
        city: { type: 'string', default: '' },
        count: { type: 'number', default: 8 },
        featured_only: { type: 'boolean', default: false },
        include_ids: { type: 'string', default: '' },
        order: { type: 'string', default: 'featured' },
    },
    edit({ attributes, setAttributes }) {
        const set = (key) => (value) => setAttributes({ [key]: value });

        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Row settings', 'staysuite-companion')}>
                        <TextControl
                            label={__('Row title', 'staysuite-companion')}
                            value={attributes.title}
                            onChange={set('title')}
                        />
                        <SelectControl
                            label={__('Source', 'staysuite-companion')}
                            value={attributes.source}
                            options={[
                                { label: __('Rooms', 'staysuite-companion'), value: 'rooms' },
                                { label: __('Hotels', 'staysuite-companion'), value: 'hotels' },
                            ]}
                            onChange={set('source')}
                        />
                        <SelectControl
                            label={__('Filter taxonomy', 'staysuite-companion')}
                            value={attributes.taxonomy}
                            options={[
                                { label: __('None', 'staysuite-companion'), value: '' },
                                { label: __('City', 'staysuite-companion'), value: 'property_city' },
                                { label: __('Category', 'staysuite-companion'), value: 'property_category' },
                                { label: __('Listing type', 'staysuite-companion'), value: 'property_action_category' },
                                { label: __('Area', 'staysuite-companion'), value: 'property_area' },
                            ]}
                            onChange={set('taxonomy')}
                        />
                        <TextControl
                            label={__('Term slug', 'staysuite-companion')}
                            help={__('e.g. coxs-bazar. Leave empty for all terms.', 'staysuite-companion')}
                            value={attributes.term}
                            onChange={set('term')}
                        />
                        <TextControl
                            label={__('City slug (hotels)', 'staysuite-companion')}
                            value={attributes.city}
                            onChange={set('city')}
                        />
                        <RangeControl
                            label={__('How many', 'staysuite-companion')}
                            value={attributes.count}
                            min={1}
                            max={24}
                            onChange={set('count')}
                        />
                        <ToggleControl
                            label={__('Featured only', 'staysuite-companion')}
                            checked={attributes.featured_only}
                            onChange={set('featured_only')}
                        />
                        <TextControl
                            label={__('Hand-picked IDs', 'staysuite-companion')}
                            help={__('Comma-separated post IDs. Overrides filters.', 'staysuite-companion')}
                            value={attributes.include_ids}
                            onChange={set('include_ids')}
                        />
                        <SelectControl
                            label={__('Order', 'staysuite-companion')}
                            value={attributes.order}
                            options={[
                                { label: __('Featured first', 'staysuite-companion'), value: 'featured' },
                                { label: __('Price: low to high', 'staysuite-companion'), value: 'price_asc' },
                                { label: __('Price: high to low', 'staysuite-companion'), value: 'price_desc' },
                                { label: __('Newest', 'staysuite-companion'), value: 'newest' },
                                { label: __('Random', 'staysuite-companion'), value: 'rand' },
                            ]}
                            onChange={set('order')}
                        />
                    </PanelBody>
                    <ProUpsell features={[__('Discount badges + scheduled sales', 'staysuite-companion'), __('Sponsored ordering', 'staysuite-companion'), __('Multi-room Add buttons', 'staysuite-companion')]} />
                </InspectorControls>
                <Preview block="ssc/listing-carousel" attributes={attributes} />
            </>
        );
    },
    save: () => null,
});
