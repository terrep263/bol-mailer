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
| Red | #B22234 (homepage uses this, not #CC2936) |
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
| Brevo Form Action | https://23fbce9e.sibforms.com/serve/MUIFAN_C1_UfZGgPzhzc-krRIMbgKWOt-1TpeC9K12Clu2n7q-4iruCpzGyx-JjkUGZoygnTOLvym2Ot5oAOVxGEhOLNjUm9gnLzTk5YMDes1B2E3J5pCMLHnnrre_ZI868X95WxL9DtHBaA1rHd4Pbsh80lHcKNk_R9QTOcLCaOFTGZ12W2Jyxvr6crz9yRcvN0EX4nyQBRW28A |
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
  └── opt-in form [bol_optin] → Brevo native form POST
        ↓
  Brevo — 5-email pre-launch sequence (bol-faith-prelaunch)

Zylvie (checkout.bookoflies.shop)
  └── purchase → /api/zylvie/webhook ✅ BUILT
        ↓
  Brevo — post-purchase sequence

/api/subscribe — opt-in handler (also used by bol-mailer direct)
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
| wordpress-plugin/bol-optin.php | Brevo opt-in form shortcode [bol_optin] ✅ v3.0 |
| wordpress-plugin/bol-grid.php | 4-column category grid shortcode [bol_grid] ✅ v1.1 |

---

## Active Sequences

### bol-faith-prelaunch
| Field | Value |
|---|---|
| List Name | BOL Faith Pre-Launch |
| From | theamerican@thebookoflies.shop / the AMerican |
| Steps | 5 emails over 14 days |

---

## WordPress Site — thebookoflies.shop

### VPS Plugin Deployment
- WordPress runs in Docker on VPS (IP: 2.24.195.19)
- Docker volume: `tk160auqvvrpw32td7lbsrba_wordpress-files`
- Plugin path: `/var/lib/docker/volumes/tk160auqvvrpw32td7lbsrba_wordpress-files/_data/wp-content/plugins/`
- Deploy plugins via: `wget -O [plugin-path] [raw-github-url]`
- Activate plugins via: `docker exec $(docker ps --filter "volume=tk160auqvvrpw32td7lbsrba_wordpress-files" -q) bash -c "php -r \"require '/var/www/html/wp-load.php'; activate_plugin('[folder/file.php]');\""
- WP-CLI is NOT available in the container
- DISALLOW_FILE_EDIT is set — plugin editor disabled in WP admin

### WordPress Categories
| ID | Name | Slug |
|---|---|---|
| 4 | Faith | faith |
| 5 | Love | love |
| 6 | Money | money |
| 7 | Relationships | relationships |
| 9 | Institutions | institutions |
| 10 | Book Of Lies (parent) | book-of-lies |

### Active Plugins (BOL custom)
| Plugin | Shortcode | Status |
|---|---|---|
| bol-optin | [bol_optin] | ✅ Active — Brevo native form, brand styled |
| bol-grid | [bol_grid] | ✅ Active — 4-column grid, 1 post per category (Faith/Love/Money/Relationships) |
| bol-autoblog | — | ✅ Active — schedule TBD (check plugin file) |

### Homepage (page ID: 233385, slug: main)
Built with Divi. Sections in order:
1. HERO — headline + stats panel
2. STATEMENT — navy quote band
3. CATEGORIES — 5 category cards (Faith/Love/Money/Relationships/Institutions)
4. QUOTE — navy full-width quote
5. SERIES — 4-book grid (Love/Faith/Money/Relationships covers)
6. WHY WRITTEN — 2-column editorial
7. RECENT POSTS — [bol_grid] shortcode (4-column, 1 post per category)
8. OPTIN — navy section with [bol_optin] shortcode

**Critical note:** WordPress REST API strips `<input>`, `<script>`, `<style>` from post/page content on save.
All interactive HTML must live in PHP plugins and be called via shortcode. Never use et_pb_code for forms or scripts.

### Blog Posts
- 54 total published posts across Faith, Love, Relationships, Institutions
- Money: 1 post published 2026-05-19 ("Why You Will Never Get Ahead")
- Duplicate/stub posts (IDs 68–90) exist — candidates for cleanup
- Post ID 92 (placeholder "[topic]" title) — set to draft 2026-05-19

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
- [ ] Confirm bol-autoblog schedule and cadence
- [ ] Clean up duplicate stub posts (IDs 68–90) — stub content only, SEO dead weight
- [ ] Add more Money category posts to build out that category

---

## Session Rules

- Read this file first, confirm loaded, then wait for instructions
- Build trigger keyword: **BTN!** — do NOT execute builds, drafts, or code until this keyword is given
- Default mode is **Suggest, don't do** — ask clarifying questions before assuming intent
- "Question and answer mode" = conversation only, no execution until "question and answer over"
- All code pushed to GitHub — never handed back to Vincent
- WordPress plugin changes: push to GitHub → wget to VPS volume → activate via docker exec php
- Never use et_pb_code for forms/scripts — always use PHP plugin + shortcode

---

## Claude Behavior Rules (from user preferences — enforced here)

These rules govern how Claude operates on this project. Violations are not acceptable.

### Communication
- Direct, no sugar-coating, encouraging, forward-thinking, respectful, formal/professional, practical, to-the-point
- Response format: clear direct answer → step-by-step explanation → alternative perspectives → practical action plan
- Never vague. Break down broad questions. Reason at full capacity.

### Code Rule
- ALL coding help delivered as complete dev prompts for Cursor/Claude/Claude Code inside copyable code windows
- No raw code dropped inline without a prompt wrapper
- No manual steps handed to Vincent
- Single ready-to-paste prompt format

### Task Execution Rule
- Always attempt tasks directly first using available tools (BOL MCP, GitHub, Supabase, etc.)
- Only request Vincent's manual involvement AFTER providing documented proof the task cannot be completed by Claude
- Never give broad homework: "check your settings," "run the tests," "review your logs," "update the file," "deploy the app" — unless Claude has proven it cannot do it

### Build Unlock
- Keyword is **BTN!**
- Do NOT initiate builds, drafts, code, or execution until BTN! is given
- Default to "Suggest, don't do" mode
- Ask clarifying questions before assuming intent
- "Question and answer mode" = conversation only, no execution

### Stack Awareness
- Vincent is not a coder — he is the product owner
- Claude is the development engineer and automation partner
- Stack: Hostinger VPS, Coolify, GitHub, Supabase, Next.js, Vue, WordPress
- Treat this stack as the normal working environment unless told otherwise

### Module Discussions
- All system/module discussions must include full end-to-end scope: UI, API, data, navigation, operations
