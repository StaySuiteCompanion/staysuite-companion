# Multi-room selection (Pro)

Available in StaySuite Companion Pro. On hotel pages each room card gains an **Add Room** button (disabled with a *Booked* label when the room is taken for the chosen dates).

* Selection persists per hotel page; a floating tray shows rooms × nights with a live total (strip dates, else 1 night).
* **Continue** opens a contact step (name/email/phone/notes) and submits through the standard group-quote endpoint with `selected_rooms[]` + `total_estimate` attached.
* Requests land in Group Requests with `_ssc_pro_room_ids` and `_ssc_pro_total_estimate` meta; confirmation shows the request reference.

No theme booking flow is altered — booking itself stays per-room on room pages.
