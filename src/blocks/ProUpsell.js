/**
 * Shared Pro upsell panel for block inspectors.
 *
 * Tasteful and wp.org-safe: locked feature list plus an outbound link.
 * Never gates existing free functionality.
 */
import { PanelBody, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function ProUpsell({ features }) {
    return (
        <PanelBody title={__('StaySuite Pro', 'staysuite-companion')} initialOpen={false}>
            <ul style={{ listStyle: 'disc', marginLeft: '18px' }}>
                {(features || []).map((feature) => (
                    <li key={feature}>{feature}</li>
                ))}
            </ul>
            <Button
                variant="primary"
                href="https://jktanmay.com"
                target="_blank"
                rel="noopener noreferrer"
                style={{ marginTop: '8px' }}
            >
                {__('Get StaySuite Pro', 'staysuite-companion')}
            </Button>
        </PanelBody>
    );
}
