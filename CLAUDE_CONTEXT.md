# BOL Mailer — Claude Session Context

> Claude must read this file at the start of every session before taking any action.
> After reading, confirm: "CLAUDE_CONTEXT.md loaded — [summary of active state]"

---

## Project Identity

| Field | Value |
|---|---|
| Project | Book of Lies Mailer (bol-mailer) |
| Repo | terrep263/bol-mailer |
| Stack | Next.js 14, TypeScript, Tailwind |
| Hosting | Hostinger VPS via Coolify |
| Coolify App UUID | z130u4n9cwb61oljr3b7pscv |
| Branch | main (auto-deploys via Coolify webhook) |

---

## Brand Identity

| Element | Value |
|---|---|
| Brand name | The Book of Lies |
| Author name | the AMerican |
| Tagline | "They used your faith against you and told you that was God's will." |
| Series hook | "They lied to you about Faith. Love. Money. Relationships." |
| Tone | Confrontational, compassionate, truth-telling, declassified |
| White | #FFFFFF |
| Navy | #1B2A4A |
| Purple | #5B21B6 |
| Gold | #C9A84C |
| Red | #CC2936 |
| Typography | Bold condensed serif (titles), wide-spaced caps (subheadings) |
| Logo | Eagle shield — gold border, bald eagle, red/white stripes, gold stars, sword |
| Cover style | White top half (navy type) + deep purple bottom half (white type + eagle shield) |
| Word mark | "DECLASSIFIED" in wide-spaced caps |

---

## Email Infrastructure

| Field | Value |
|---|---|
| Provider | Brevo (formerly Sendinblue) |
| Account | Vincent Polite — free plan (300 emails/day) |
| API Base | https://api.brevo.com/v3 |
| Sender Email | theamerican@thebookoflies.shop |
| Sender Name | the AMerican |
| Previous provider | Listmonk — fully removed and replaced by Brevo |

---

## Environment Variables (Coolify)

| Key | Purpose |
|---|---|
| BREVO_API_KEY | Brevo REST API auth |
| BREVO_SENDER_EMAIL | theamerican@thebookoflies.shop (fallback ref) |
| ANTHROPIC_API_KEY | Claude AI for dynamic email generation |
| CRON_SECRET | Protects GET /api/cron — must match header x-cron-secret |
| NEXT_PUBLIC_SITE_URL | Public app URL |
| ZYLVIE_API_KEY | Zylvie API — license key verification only |

---

## Architecture

```
WordPress (thebookoflies.shop)
  └── opt-in form → POST /api/subscribe

/api/subscribe
  ├── Validates email + firstName + sequenceId
  ├── Creates/gets Brevo list via createList()
  ├── Adds subscriber via addSubscriber()
  ├── Generates Email 1 via Claude (generateEmail())
  ├── Sends via Brevo sendTransactionalEmail()
  └── Updates subscriber attribs (sequence_step, last_sent_at)

/api/cron (GET, protected by CRON_SECRET header)
  ├── Loops all SEQUENCES
  ├── Gets subscribers per list
  ├── Checks delay days since last send
  ├── Generates next email via Claude
  └── Sends + updates attribs

/api/lists/setup (POST)
  └── Creates Brevo lists for all sequences (idempotent)

/api/zylvie/webhook (POST) — PENDING BUILD
  ├── Receives Zylvie purchase event
  ├── Verifies webhook secret
  ├── Maps product ID → sequence
  ├── Adds buyer to Brevo list
  └── Fires post-purchase email sequence
```

---

## Key Files

| File | Purpose |
|---|---|
| lib/listmonk.ts | Brevo API client — all contact + send functions |
| lib/sequences.ts | Sequence definitions (steps, delays, brand context) |
| lib/claude.ts | Claude AI email generation |
| app/api/subscribe/route.ts | Opt-in handler — triggers Email 1 |
| app/api/cron/route.ts | Daily cron — sends sequence steps 2–5 |
| app/api/lists/setup/route.ts | One-time list creation in Brevo |
| app/api/zylvie/webhook/route.ts | Zylvie purchase webhook — PENDING BUILD |
| wordpress-plugin/ | WP plugin for opt-in form integration |

---

## Active Sequences

### bol-faith-prelaunch
| Field | Value |
|---|---|
| List Name | BOL Faith Pre-Launch |
| From | theamerican@thebookoflies.shop / the AMerican |
| Target Action | Join waitlist at thebookoflies.online/waitlist |
| Steps | 5 emails over 14 days |

| Step | Delay | Description |
|---|---|---|
| 1 | Day 0 | Welcome + Chapter 1 delivery |
| 2 | Day 3 | Validation — you were not wrong to question |
| 3 | Day 7 | Name the lies — tease the book |
| 4 | Day 10 | Soft pitch — join the waitlist |
| 5 | Day 14 | Final urgency — launch is coming |

---

## Book of Lies: Faith — Product

| Field | Value |
|---|---|
| Platform | Zylvie |
| Zylvie Product ID | D8njKL8ni |
| Zylvie Product URL | https://checkout.bookoflies.shop/p/book-of-lies-faith |
| Price | $27 |
| Format | PDF + EPUB |
| PDF URL | https://bookoflies-853537565894-us-east-1-an.s3.us-east-1.amazonaws.com/The+Book+Of+Lies+Faith.pdf |
| EPUB URL | https://bookoflies-853537565894-us-east-1-an.s3.us-east-1.amazonaws.com/the+book+of+lies+faith.epub |
| S3 Bucket | bookoflies-853537565894-us-east-1-an |
| S3 Region | us-east-1 |
| Webhook endpoint | /api/zylvie/webhook — PENDING BUILD |
| WordPress sales page | PENDING BUILD — thebookoflies.shop |

---

## Zylvie Store

| Field | Value |
|---|---|
| Store URL | https://checkout.bookoflies.shop |
| Username | bookoflies |
| Brand name | The Book of Lies |
| Stripe | Connected ✅ |
| Plan | Plan 3 (webhooks enabled) |
| API | License key verification only — no product management API |

---

## CORS

Allowed origins for /api/subscribe:
- https://thebookoflies.shop
- https://thebookoflies.online

---

## Known Issues / Pending

- [ ] Brevo sender `theamerican@thebookoflies.shop` — verify in Brevo dashboard
- [ ] Email 1 welcome copy — wire in fixed template (not AI-generated)
- [ ] CRON_SECRET value — confirm set in Coolify
- [ ] ZYLVIE_WEBHOOK_SECRET env var — add to Coolify once generated in Zylvie
- [ ] Build /api/zylvie/webhook endpoint — BTN! pending
- [ ] Build WordPress sales page on thebookoflies.shop — BTN! pending
- [ ] Upload PDF + EPUB to Zylvie product
- [ ] Connect Brevo in Zylvie Settings → Integrations
- [ ] Rewrite product description around new tagline

---

## Brevo IP Whitelist (if needed)

Claude's servers rotate IPs. If Brevo blocks API calls, add to Brevo → Security → Authorised IPs:
- 35.238.245.102
- 34.135.250.196

---

## Session Rules

- Read this file first, confirm loaded, then wait for instructions
- Build trigger keyword: **BTN!**
- No builds, drafts, or code without BTN!
- All code delivered as push to GitHub — never hand back to Vincent
- Never ask Vincent to manually edit files, run commands, or check logs unless documented proof it cannot be done
