/**
 * Group booking block editor.
 */
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import Preview from './Preview';
import ProUpsell from './ProUpsell';

registerBlockType('ssc/group-booking', {
    title: __('StaySuite Group Booking', 'staysuite-companion'),
    icon: 'groups',
    category: 'widgets',
    attributes: {
        title: { type: 'string', default: '' },
    },
    edit({ attributes, setAttributes }) {
        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Form settings', 'staysuite-companion')}>
                        <TextControl
                            label={__('Heading', 'staysuite-companion')}
                            value={attributes.title}
                            onChange={(title) => setAttributes({ title })}
                        />
                    </PanelBody>
                    <ProUpsell features={[__('Quote-to-invoice pipeline', 'staysuite-companion'), __('Email templates + reminders', 'staysuite-companion'), __('Upsell add-ons', 'staysuite-companion')]} />
                </InspectorControls>
                <Preview block="ssc/group-booking" attributes={attributes} />
            </>
        );
    },
    save: () => null,
});
