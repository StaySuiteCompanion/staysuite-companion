# AI group concierge (Pro)

Available in StaySuite Companion Pro. A floating concierge on hotel pages chats with groups (English + Bangla), searches real rooms, and books through the standard quote flow.

* **Bring your own key** — Hotels → AI Settings: ChatGPT, Claude, Gemini, Grok, DeepSeek or any OpenAI-compatible endpoint, per-provider keys (never rendered back), model override, one-click connection test.
* **Grounded by design** — the model only narrates server-provided inventory (rooms, prices, facilities, live availability). It cannot invent prices or rooms.
* **Quote handoff** — on "yes, book" it collects name/email in chat and submits via `ssc_group_quote` with matched room IDs + total, same pipeline as the tray.
* **Abuse-safe** — public endpoint with IP rate limits, message caps and truncated history; keys never leave the server.
