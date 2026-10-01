/**
 * Batched room -> hotel resolver with per-page caching.
 *
 * Card badges mount independently, but all requests made in the same
 * tick are merged into a single admin-ajax call.
 */

const cache = new Map();
const pending = new Map();
let scheduled = false;

/**
 * Send one batched request for every queued room ID.
 *
 * @return {void}
 */
function flushQueue() {
    scheduled = false;

    if (pending.size === 0 || typeof sscCards === 'undefined') {
        pending.clear();
        return;
    }

    const ids = Array.from(pending.keys());
    const waiting = pending;
    pending.clear();

    const body = new URLSearchParams();
    body.append('action', 'ssc_resolve_hotels');
    body.append('nonce', sscCards.nonce);
    ids.forEach((id) => body.append('ids[]', String(id)));

    fetch(sscCards.ajaxurl, {
        method: 'POST',
        credentials: 'same-origin',
        body,
    })
        .then((response) => response.json())
        .then((res) => {
            const map = res && res.success && res.data ? res.data : {};
            ids.forEach((id) => {
                const hotel = map[id] || map[String(id)] || null;
                cache.set(id, hotel);
                (waiting.get(id) || []).forEach((resolve) => resolve(hotel));
            });
        })
        .catch(() => {
            ids.forEach((id) => {
                cache.set(id, null);
                (waiting.get(id) || []).forEach((resolve) => resolve(null));
            });
        });
}

/**
 * Resolve the hotel for a room ID, using cache when available.
 *
 * @param {number} roomId Room (estate_property) post ID.
 * @return {Promise<Object|null>} Hotel {name, url} or null.
 */
export function getHotel(roomId) {
    const id = parseInt(roomId, 10);

    if (cache.has(id)) {
        return Promise.resolve(cache.get(id));
    }

    return new Promise((resolve) => {
        if (!pending.has(id)) {
            pending.set(id, []);
        }
        pending.get(id).push(resolve);

        if (!scheduled) {
            scheduled = true;
            queueMicrotask(flushQueue);
        }
    });
}
