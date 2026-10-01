/**
 * Hotel badge rendered inside a listing card.
 *
 * Renders nothing until the room is confirmed to belong to a hotel,
 * so standalone listings are untouched.
 */
import { useEffect, useState } from 'react';
import { getHotel } from './api';

export default function HotelBadge({ roomId }) {
    const [hotel, setHotel] = useState(null);

    useEffect(() => {
        let alive = true;

        getHotel(roomId).then((result) => {
            if (alive) {
                setHotel(result);
            }
        });

        return () => {
            alive = false;
        };
    }, [roomId]);

    if (!hotel) {
        return null;
    }

    return (
        <span className="ssc-hotel-link-wrap">
            <span className="ssc-hotel-link-label"> · </span>
            <a className="ssc-hotel-link" href={hotel.url}>
                {hotel.name}
            </a>
        </span>
    );
}
