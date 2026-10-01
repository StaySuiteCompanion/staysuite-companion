# Homepage blocks & shortcodes

Five dynamic (server-rendered) blocks with live editor previews via `ssc/v1/preview`. Every block has an equivalent shortcode.

## `ssc/hero-search` / `[ssc_hero]`

Full-bleed cover (page featured image or `image_id`) with title, subtitle and the theme's own search widget (location, dates, guests, circular submit).

Attributes: `title`, `subtitle`, `image_id`, `search_mode` (`theme`|`simple`|`none`), `show_search` (`1`|`0`), plus core `align` (use `full`).

```
[ssc_hero title="Find your next stay" subtitle="Hotels across Bangladesh" image_id="123"]
```

## `ssc/term-tablets` / `[ssc_term_tablets]`

Glass gradient pills linking term archives, with stay counts.

Attributes: `taxonomy` (`property_city`|`property_category`|`property_action_category`|`property_area`), `number` (default 6), `hide_empty` (`1`|`0`).

```
[ssc_term_tablets taxonomy="property_action_category" number="6" hide_empty="1"]
```

## `ssc/listing-carousel` / `[ssc_listing_carousel]`

Horizontal snap carousel of theme listing cards with gallery-style flanking arrows.

Attributes: `title`, `source` (`rooms`|`hotels`), `taxonomy`, `term`, `city`, `count` (default 8), `featured_only` (`1`|`0`), `include_ids`, `order` (`featured`|…).

```
[ssc_listing_carousel title="Popular stays" source="rooms" count="8"]
```

## `ssc/payment-strip` / `[ssc_payment_strip]`

Label + banner image (payment logos).

Attributes: `title` (default `Pay With`), `image_id`.

## `ssc/group-booking` / `[ssc_group_booking]`

Standalone group quote form (see [Group booking](group-booking.md)). Attribute: `title`.
