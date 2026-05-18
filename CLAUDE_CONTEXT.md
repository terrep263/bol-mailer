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
| NEXT_PUBLIC_SITE_URL | https://mailer.thebookoflies.online |
| ZYLVIE_API_KEY | Zylvie API — license key verification only |
| ZYLVIE_WEBHOOK_SECRET | Set from Zylvie Settings → API/Developers → Webhooks |
| AWS_ACCESS_KEY_ID | IAM user: productdyno |
| AWS_SECRET_ACCESS_KEY | IAM user: productdyno |
| AWS_S3_BUCKET | bookoflies-853537565894-us-east-1-an |
| AWS_S3_REGION | us-east-1 |

---

## S3 Operations

Claude can upload files to S3 directly via boto3 using credentials from Coolify env vars.
- Bucket has public read policy set — no ACL needed on upload
- Upload syntax: no ExtraArgs ACL — just ContentType
- IAM user: productdyno — has AmazonS3FullAccess policy attached

---

## Architecture

```
WordPress (thebookoflies.shop)
  └── opt-in form → POST /api/subscribe
        ↓
  bol-mailer /api/subscribe
        ↓
  Brevo — 5-email pre-launch sequence

Zylvie (checkout.bookoflies.shop)
  └── purchase → /api/zylvie/webhook ✅ BUILT
        ↓
  Brevo — post-purchase sequence

/api/subscribe — opt-in handler
/api/cron — daily sequence processor
/api/zylvie/webhook — purchase handler ✅
/api/lists/setup — one-time list creation
```

---

## Key Files

| File | Purpose |
|---|---|
| lib/listmonk.ts | Brevo API client |
| lib/sequences.ts | Sequence definitions |
| lib/claude.ts | Claude AI email generation |
| app/api/subscribe/route.ts | Opt-in handler |
| app/api/cron/route.ts | Daily cron |
| app/api/lists/setup/route.ts | List creation |
| app/api/zylvie/webhook/route.ts | Zylvie purchase webhook ✅ |

---

## Active Sequences

### bol-faith-prelaunch
| Field | Value |
|---|---|
| List Name | BOL Faith Pre-Launch |
| From | theamerican@thebookoflies.shop / the AMerican |
| Steps | 5 emails over 14 days |

---

## Book of Lies: Faith — Product

| Field | Value |
|---|---|
| Platform | Zylvie |
| Zylvie Product ID | D8njKL8ni |
| Checkout URL | https://checkout.bookoflies.shop/p/book-of-lies-faith |
| WordPress Sales Page | https://thebookoflies.shop/book-of-lies-faith/ ✅ LIVE |
| Price | $27 |
| Format | PDF + EPUB |
| PDF URL | https://bookoflies-853537565894-us-east-1-an.s3.us-east-1.amazonaws.com/The+Book+Of+Lies+Faith.pdf |
| EPUB URL | https://bookoflies-853537565894-us-east-1-an.s3.us-east-1.amazonaws.com/the+book+of+lies+faith.epub |
| Workbook URL | https://bookoflies-853537565894-us-east-1-an.s3.us-east-1.amazonaws.com/BookOfLies_Faith_Workbook.pdf |
| Workbook Price | $17 (upsell in Zylvie) |

---

## Zylvie Store

| Field | Value |
|---|---|
| Store URL | https://checkout.bookoflies.shop |
| Plan | Plan 3 (webhooks enabled) |
| Stripe | Connected ✅ |
| Brevo | Connected → BOL Faith Pre-Launch ✅ |
| DNS | checkout CNAME → domains.zylvie.com ✅ |
| SSL | Zylvie provisioning — check status |

---

## Pending

- [ ] Brevo sender `theamerican@thebookoflies.shop` — verify in Brevo dashboard
- [ ] ZYLVIE_WEBHOOK_SECRET — get from Zylvie → Settings → API/Developers → Webhooks and update in Coolify
- [ ] Upload PDF + EPUB files to Zylvie product (TUS server error — workaround: use S3 URLs directly)
- [ ] Set up workbook as $17 upsell in Zylvie
- [ ] Gated page + Success Email in Zylvie product
- [ ] Verify checkout.bookoflies.shop SSL fully provisioned

---

## Session Rules

- Read this file first, confirm loaded, then wait for instructions
- Build trigger keyword: **BTN!**
- All code pushed to GitHub — never handed back to Vincent
- Never ask Vincent to run commands unless documented proof it cannot be done
