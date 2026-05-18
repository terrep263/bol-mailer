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
| LISTMONK_URL / LISTMONK_USER / LISTMONK_PASSWORD | Legacy — no longer used, can be removed |

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

/api/productdyno/webhook (POST) — PENDING BUILD
  ├── Receives ProductDyno member.created event
  ├── Verifies secret
  ├── Maps product ID → sequence
  ├── Adds buyer to Brevo list
  └── Fires Email 1 with download links
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
| app/api/productdyno/webhook/route.ts | ProductDyno purchase webhook — PENDING BUILD |
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
| Platform | ProductDyno |
| Price | $27 |
| Format | PDF + EPUB |
| PDF URL | https://bookoflies-853537565894-us-east-1-an.s3.us-east-1.amazonaws.com/The+Book+Of+Lies+Faith.pdf |
| EPUB URL | https://bookoflies-853537565894-us-east-1-an.s3.us-east-1.amazonaws.com/the+book+of+lies+faith.epub |
| S3 Bucket | bookoflies-853537565894-us-east-1-an |
| S3 Region | us-east-1 |
| ProductDyno Product ID | PENDING — retrieve from ProductDyno product URL |
| Webhook endpoint | /api/productdyno/webhook — PENDING BUILD |
| WordPress sales page | PENDING BUILD — thebookoflies.shop |

---

## CORS

Allowed origins for /api/subscribe:
- https://thebookoflies.shop
- https://thebookoflies.online

---

## Known Issues / Pending

- [ ] Brevo sender `theamerican@thebookoflies.shop` must be verified in Brevo dashboard
- [ ] Legacy LISTMONK_* env vars in Coolify can be deleted
- [ ] Email 1 welcome copy — wire in fixed template (not AI-generated)
- [ ] CRON_SECRET value — confirm set in Coolify
- [ ] ProductDyno product ID — retrieve from product URL
- [ ] PRODUCTDYNO_SECRET env var — add to Coolify once generated
- [ ] Build /api/productdyno/webhook endpoint
- [ ] Build WordPress sales page on thebookoflies.shop
- [ ] ProductDyno 500 error on TUS upload — reported to support, using S3 direct URLs as workaround

---

## Brevo IP Whitelist (if needed)

Claude's servers rotate IPs. If Brevo blocks API calls, add these to Brevo → Security → Authorised IPs:
- 35.238.245.102
- 34.135.250.196

---

## Session Rules

- Read this file first, confirm loaded, then wait for instructions
- Build trigger keyword: **BTN!**
- No builds, drafts, or code without BTN!
- All code delivered as push to GitHub — never hand back to Vincent
- Never ask Vincent to manually edit files, run commands, or check logs unless Claude has documented proof it cannot do it
