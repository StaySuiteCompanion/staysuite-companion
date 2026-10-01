/**
 * First-party block preview.
 *
 * POSTs attributes to the ssc/v1/preview REST route and renders the
 * returned HTML. Replaces @wordpress/server-side-render, whose
 * ref/effect machinery conflicts with the iframed editor canvas.
 */
import { useEffect, useRef, useState } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { RawHTML } from '@wordpress/element';
import { Spinner } from '@wordpress/components';

export default function Preview({ block, attributes }) {
    const [html, setHtml] = useState('');
    const [loading, setLoading] = useState(true);
    const timer = useRef(null);

    useEffect(() => {
        setLoading(true);
        clearTimeout(timer.current);

        timer.current = setTimeout(() => {
            apiFetch({
                path: '/ssc/v1/preview',
                method: 'POST',
                data: { block, attributes },
            })
                .then((res) => {
                    setHtml(res && res.html ? res.html : '');
                    setLoading(false);
                })
                .catch(() => setLoading(false));
        }, 250);

        return () => clearTimeout(timer.current);
        // Stringified attributes keep the effect keyed on values, not identity.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [block, JSON.stringify(attributes)]);

    if (loading && !html) {
        return <Spinner />;
    }

    return <RawHTML>{html}</RawHTML>;
}
