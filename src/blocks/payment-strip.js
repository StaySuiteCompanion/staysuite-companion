/**
 * Payment strip block editor.
 */
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { PanelBody, TextControl, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import Preview from './Preview';
import ProUpsell from './ProUpsell';

registerBlockType('ssc/payment-strip', {
    title: __('StaySuite Payment Strip', 'staysuite-companion'),
    icon: 'money-alt',
    category: 'widgets',
    attributes: {
        title: { type: 'string', default: 'Pay With' },
        image_id: { type: 'number', default: 0 },
    },
    edit({ attributes, setAttributes }) {
        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Payment strip settings', 'staysuite-companion')}>
                        <TextControl
                            label={__('Label', 'staysuite-companion')}
                            value={attributes.title}
                            onChange={(title) => setAttributes({ title })}
                        />
                        <MediaUploadCheck>
                            <MediaUpload
                                allowedTypes={['image']}
                                value={attributes.image_id}
                                onSelect={(media) => setAttributes({ image_id: media.id })}
                                render={({ open }) => (
                                    <Button variant="secondary" onClick={open}>
                                        {attributes.image_id
                                            ? __('Change banner image', 'staysuite-companion')
                                            : __('Pick banner image', 'staysuite-companion')}
                                    </Button>
                                )}
                            />
                        </MediaUploadCheck>
                    </PanelBody>
                    <ProUpsell features={[__('Clickable payment links', 'staysuite-companion'), __('UPI + crypto badges', 'staysuite-companion')]} />
                </InspectorControls>
                <Preview block="ssc/payment-strip" attributes={attributes} />
            </>
        );
    },
    save: () => null,
});
