/**
 * Prev/next controls for a server-rendered carousel track.
 *
 * Mounted into existing markup by src/index.js — the track and its
 * cards are plain HTML, so rows work with and without JavaScript.
 * Controls hide themselves when everything already fits (e.g. a
 * single item), which also covers post-load font/image shifts via a
 * delayed re-check.
 */
import { useEffect, useState } from 'react';
import { __ } from '@wordpress/i18n';

export default function CarouselNav({ track }) {
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        if (!track) {
            return undefined;
        }
        const check = () => {
            setVisible(track.scrollWidth > track.clientWidth + 2);
        };
        check();
        window.addEventListener('resize', check);
        const settled = setTimeout(check, 600);
        return () => {
            window.removeEventListener('resize', check);
            clearTimeout(settled);
        };
    }, [track]);

    if (!visible) {
        return null;
    }

    const scroll = (direction) => {
        track.scrollBy({
            left: direction * track.clientWidth * 0.8,
            behavior: 'smooth',
        });
    };

    return (
        <div className="ssc-carousel-nav">
            <button
                type="button"
                className="ssc-carousel-prev"
                aria-label={__('Previous', 'staysuite-companion')}
                onClick={() => scroll(-1)}
            >
                ‹
            </button>
            <button
                type="button"
                className="ssc-carousel-next"
                aria-label={__('Next', 'staysuite-companion')}
                onClick={() => scroll(1)}
            >
                ›
            </button>
        </div>
    );
}
