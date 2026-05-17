# Claude Tool & Access Reference

> Master reference for what Claude can and cannot do in any Vincent Polite session.
> Claude must consult this before claiming it can or cannot perform any action.
> Last updated: 2026-05-17

---

## Critical Distinctions (Read First)

| Tool | What it actually is | Common mistake |
|---|---|---|
| `bash_tool` | Claude's own Linux server in the cloud | Confused with Vincent's computer or VPS |
| `my-filesystem` | Vincent's LOCAL computer files | Confused with Claude's server or VPS |
| `Claude in Chrome` | Automation inside Vincent's browser | Confused with Claude's web_fetch |
| `web_fetch` | Claude fetching a URL server-side | NOT the same as browsing in Vincent's browser |
| `Hostinger MCP` | VPS + DNS + domain management API | Does NOT mean Claude can access cPanel or file manager |
| `Coolify MCP` | App/env/deploy management | Claude can redeploy but cannot SSH into the server |

---

## Always Available (No Setup Required)

| Tool | What Claude Can Do |
|---|---|
| `web_search` | Search the internet for current information |
| `web_fetch` | Fetch full content of any URL |
| `bash_tool` | Run commands on Claude's own Linux container — install packages, run scripts, create files, test code |
| `image_search` | Search and return images |
| `conversation_search` | Search Vincent's past Claude conversations by topic |
| `recent_chats` | List recent Claude conversations by date |
| `memory_user_edits` | Read, add, update, or remove Claude's persistent memory |
| `weather_fetch` | Get current weather for any location |
| `places_search` | Search Google Places for locations/businesses |
| `visualizer` | Create inline SVG diagrams, charts, and HTML widgets |

---

## MCP Tools (Connected — Require `tool_search` Before First Use Per Session)

| Tool | Access Level | What Claude Can Do |
|---|---|---|
| **git2 (GitHub)** | Full read/write | Read files, push commits, create/update files, manage PRs, search repos, create branches |
| **Coolify** | Full management | List/start/stop/restart apps, manage env vars, read logs, trigger redeploys |
| **Hostinger MCP** | VPS + DNS + Domains | Manage DNS records, domain settings, VPS projects, firewall, snapshots |
| **BOL MCP** | WordPress API | Read/write posts, pages, menus, categories, media on thebookoflies.shop |
| **my-filesystem** | Vincent's local machine | Read/write files on Vincent's computer in allowed directories only |
| **Supabase** | Full DB access | Run SQL, apply migrations, manage schema, read/write tables, edge functions |
| **Google Drive** | Read + search | Search and fetch Vincent's Drive files and Google Docs |
| **Gmail** | Read + compose | Read emails, draft and send with explicit permission |
| **Google Calendar** | Read + write | Read events, create/update calendar entries with permission |
| **Vercel** | Deployment | Manage Vercel projects, deployments, env vars, domains |
| **Canva** | Design | Create and manage Canva designs |
| **bol-autoblog** | BOL content pipeline | Pull Reddit/RSS/GNews signals, generate titles, queue WordPress posts |

---

## Deferred MCP Tools (Need `tool_search` — Heavier Load)

| Tool | What It Does | When to Use |
|---|---|---|
| **Claude in Chrome** | Full browser automation inside Vincent's Chrome | When a task requires clicking, form filling, or navigating a live site that cannot be done via API |
| **Filesystem (alt)** | Secondary filesystem access | If my-filesystem is unavailable |

---

## What Claude CANNOT Do (Hard Limits)

| Action | Reason |
|---|---|
| Enter passwords on Vincent's behalf | Security — prohibited |
| Complete purchases or financial transactions | Security — prohibited |
| Permanently delete files or emails | Irreversible — prohibited |
| Modify security permissions or sharing settings | Security — prohibited |
| Create new accounts on any platform | Security — prohibited |
| Access Vincent's VPS terminal directly | No SSH — Coolify and Hostinger MCP are the only access paths |
| Access cPanel, file manager, or phpMyAdmin | No direct Hostinger panel access — DNS/VPS API only |
| Bypass CAPTCHA or bot detection | Security — prohibited |
| Read browser-stored passwords or autofill data | Privacy — prohibited |

---

## Access Claude Does NOT Have (Common Assumptions)

| Assumed Access | Reality |
|---|---|
| Vincent's local terminal | ❌ No — bash_tool is Claude's server only |
| Vincent's VS Code | ❌ No — Claude cannot open or edit files in VS Code directly |
| Hostinger file manager | ❌ No — only VPS/DNS management via Hostinger MCP |
| Brevo dashboard | ❌ No — only Brevo REST API via bash_tool or code |
| Stripe dashboard | ❌ No — only Stripe API if keys are provided |
| Facebook, TikTok, Instagram | ❌ No — no social platform APIs connected |
| WordPress admin panel (UI) | ❌ No — only BOL MCP for thebookoflies.shop via REST API |
| Vincent's email inbox (live) | ⚠️ Gmail MCP connected but requires explicit permission per action |

---

## Decision Tree — Before Claiming Access

```
Can Claude do this task?
│
├── Is it a web search or URL fetch? → YES, use web_search / web_fetch
├── Is it code execution or file testing? → YES, use bash_tool (Claude's server)
├── Is it a GitHub file read/write/push? → YES, use git2
├── Is it a Coolify app/env/deploy action? → YES, use coolify MCP
├── Is it a Supabase DB action? → YES, use Supabase MCP
├── Is it a DNS or VPS action? → YES, use hostinger-mcp
├── Is it a WordPress post/page action on BOL? → YES, use BOL MCP
├── Is it reading/writing Vincent's local files? → YES, use my-filesystem (if connected)
├── Is it navigating a live website UI? → MAYBE, use Claude in Chrome (Vincent's browser must be open)
├── Does it require a password? → NO — tell Vincent to do it
├── Does it require a purchase? → NO — tell Vincent to do it
└── Is it an SSH or terminal action on VPS? → NO — cannot do this
```

---

## Project-Specific Access Notes

### bol-mailer
- GitHub: `terrep263/bol-mailer` ✅
- Coolify UUID: `z130u4n9cwb61oljr3b7pscv` ✅
- Brevo: API via bash_tool using BREVO_API_KEY ✅
- WordPress (BOL MCP): thebookoflies.shop ✅

### SMN (shopmyneighborhood.com)
- GitHub: `terrep263/smnversion2` ✅
- Coolify: connected ✅
- Supabase: `ajzgwrsdlzhwthlhsesn` ✅
- Hostinger VPS: ID 1582334, IP 2.24.195.19 ✅

### SnapWorxx
- GitHub: connected ✅
- Coolify UUID: `kga5odnj563udp0wybfvjobg` ✅
- Supabase: `ofmzpgbuawtwtzgrtiwr` ✅
