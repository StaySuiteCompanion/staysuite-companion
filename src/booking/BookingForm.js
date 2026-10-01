/**
 * Group booking extras form.
 *
 * When mounted next to a theme search bar in Group mode, location, dates
 * and guests are read live from the theme fields (autocomplete,
 * datepickers and guest logic included) and this form collects only the
 * group-specific extras. Standalone (shortcode/block without a theme
 * search nearby) it renders its own Where/dates/guests fields.
 */
import { useEffect, useRef, useState } from 'react';
import { __ } from '@wordpress/i18n';

/**
 * Find the active theme search wrapper feeding this form.
 *
 * Matches the classic template root and the Elementor widget root alike;
 * SearchMode flags whichever wrapper it enhances with ssc-group-active.
 *
 * @return {Element|null} Theme search wrapper or null.
 */
function findThemeSearch() {
    return document.querySelector('.advanced_search_form_wrapper.ssc-group-active, .search_wr_elementor.ssc-group-active');
}

/**
 * Read a named field from the theme search form.
 *
 * @param {Element|null} root Theme search wrapper.
 * @param {string} name Field name.
 * @return {string} Field value or empty string.
 */
function readThemeField(root, name) {
    if (!root) {
        return '';
    }
    const field = root.querySelector(`[name="${name}"]`);
    return field ? field.value : '';
}

const initialForm = {
    city: '',
    check_in: '',
    check_out: '',
    rooms: '1',
    guests: '2',
    male: '',
    female: '',
    budget_min: '',
    budget_max: '',
    name: '',
    email: '',
    phone: '',
    requirements: '',
};

export default function BookingForm({ title }) {
    const [form, setForm] = useState(initialForm);
    const [external, setExternal] = useState(false);
    const [state, setState] = useState({ status: 'idle', message: '', matches: [], total: 0 });

    const set = (key) => (event) => {
        setForm((prev) => ({ ...prev, [key]: event.target.value }));
    };

    const doSubmit = () => {
        setState({ status: 'loading', message: '', matches: [], total: 0 });

        const themeSearch = findThemeSearch();
        const payload = { ...form };
        if (themeSearch) {
            payload.location_text = readThemeField(themeSearch, 'search_location');
            payload.check_in = readThemeField(themeSearch, 'check_in');
            payload.check_out = readThemeField(themeSearch, 'check_out');
            const themeGuests = parseInt(readThemeField(themeSearch, 'guest_no'), 10);
            payload.guests = themeGuests > 0 ? String(themeGuests) : form.guests;
            payload.city = '';
        }

        const body = new URLSearchParams();
        body.append('action', 'ssc_group_quote');
        body.append('nonce', sscBooking.quote_nonce);
        Object.entries(payload).forEach(([key, value]) => body.append(key, value));

        fetch(sscBooking.ajaxurl, { method: 'POST', credentials: 'same-origin', body })
            .then((response) => response.json())
            .then((res) => {
                if (res && res.success) {
                    setState({
                        status: 'done',
                        message: '',
                        matches: res.data.matches || [],
                        total: res.data.total || 0,
                    });
                } else {
                    setState({
                        status: 'error',
                        message:
                            (res && res.data && res.data.message) ||
                            __('Something went wrong. Please try again.', 'staysuite-companion'),
                        matches: [],
                        total: 0,
                    });
                }
            })
            .catch(() =>
                setState({
                    status: 'error',
                    message: __('Something went wrong. Please try again.', 'staysuite-companion'),
                    matches: [],
                    total: 0,
                })
            );
    };

    const submitRef = useRef(null);
    submitRef.current = doSubmit;

    useEffect(() => {
        setExternal(!!findThemeSearch());
        const trigger = (event) => {
            event.preventDefault();
            if (submitRef.current) {
                submitRef.current();
            }
        };
        window.addEventListener('ssc-group-quote-submit', trigger);
        return () => window.removeEventListener('ssc-group-quote-submit', trigger);
    }, []);

    const submit = (event) => {
        event.preventDefault();
        doSubmit();
    };

    const cities = sscBooking.cities || [];

    return (
        <div className="ssc-booking-form-wrap">
            {title && <h2 className="ssc-booking-title">{title}</h2>}
            <form className="ssc-booking-form" onSubmit={submit}>
                {!external && (
                    <>
                        <label>
                            <span>{__('Where?', 'staysuite-companion')}</span>
                            <select value={form.city} onChange={set('city')}>
                                <option value="">{__('Anywhere', 'staysuite-companion')}</option>
                                {cities.map((city) => (
                                    <option key={city.slug} value={city.slug}>
                                        {city.name}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <label>
                            <span>{__('Check in', 'staysuite-companion')}</span>
                            <input type="date" value={form.check_in} onChange={set('check_in')} />
                        </label>
                        <label>
                            <span>{__('Check out', 'staysuite-companion')}</span>
                            <input type="date" value={form.check_out} onChange={set('check_out')} />
                        </label>
                        <label>
                            <span>{__('Guests', 'staysuite-companion')}</span>
                            <input type="number" min="1" value={form.guests} onChange={set('guests')} required />
                        </label>
                    </>
                )}
                <label>
                    <span>{__('Rooms', 'staysuite-companion')}</span>
                    <input type="number" min="1" value={form.rooms} onChange={set('rooms')} required />
                </label>
                <label>
                    <span>{__('Male', 'staysuite-companion')}</span>
                    <input type="number" min="0" value={form.male} onChange={set('male')} placeholder="0" />
                </label>
                <label>
                    <span>{__('Female', 'staysuite-companion')}</span>
                    <input type="number" min="0" value={form.female} onChange={set('female')} placeholder="0" />
                </label>
                <label>
                    <span>{__('Budget min', 'staysuite-companion')}</span>
                    <input
                        type="number"
                        min="0"
                        value={form.budget_min}
                        onChange={set('budget_min')}
                        placeholder={__('Per night', 'staysuite-companion')}
                    />
                </label>
                <label>
                    <span>{__('Budget max', 'staysuite-companion')}</span>
                    <input
                        type="number"
                        min="0"
                        value={form.budget_max}
                        onChange={set('budget_max')}
                        placeholder={__('Per night', 'staysuite-companion')}
                    />
                </label>
                <label>
                    <span>{__('Your name', 'staysuite-companion')}</span>
                    <input type="text" value={form.name} onChange={set('name')} required />
                </label>
                <label>
                    <span>{__('Email', 'staysuite-companion')}</span>
                    <input type="email" value={form.email} onChange={set('email')} required />
                </label>
                <label>
                    <span>{__('Phone', 'staysuite-companion')}</span>
                    <input type="tel" value={form.phone} onChange={set('phone')} />
                </label>
                <label className="ssc-booking-full">
                    <span>{__('Extra requirements', 'staysuite-companion')}</span>
                    <textarea
                        rows="4"
                        value={form.requirements}
                        onChange={set('requirements')}
                        placeholder={__('Food, transport, event hall — anything we should quote for…', 'staysuite-companion')}
                    />
                </label>
                <button type="submit" className="ssc-booking-submit" disabled={state.status === 'loading'}>
                    {state.status === 'loading'
                        ? __('Finding stays…', 'staysuite-companion')
                        : __('Get quote', 'staysuite-companion')}
                </button>
            </form>

            {state.status === 'error' && <p className="ssc-booking-error">{state.message}</p>}

            {state.status === 'done' && (
                <div className="ssc-booking-results">
                    <h3>
                        {state.total > 0
                            ? __('Suggested stays in your budget', 'staysuite-companion')
                            : __('No stays matched — our team will still quote you by email.', 'staysuite-companion')}
                    </h3>
                    <div className="ssc-booking-grid">
                        {state.matches.map((match) => (
                            <article key={match.id} className="ssc-booking-card">
                                {match.image && (
                                    <a href={match.url}>
                                        <img src={match.image} alt="" loading="lazy" />
                                    </a>
                                )}
                                <h4>
                                    <a href={match.url}>{match.title}</a>
                                </h4>
                                <div className="ssc-booking-card-meta">
                                    {[match.price && `${match.price}`, match.guests > 0 && `${match.guests} guests`]
                                        .filter(Boolean)
                                        .join(' · ')}
                                </div>
                            </article>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}
