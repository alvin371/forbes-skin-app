---
name: Acneno
description: Calm, dense operations platform for Indonesian SMBs, HR-first.
colors:
  primary: "#1255CC"
  primary-tint: "#1155CC1A"
  primary-deep: "#0A3277"
  heading-navy: "#0D1458"
  card-indigo: "#3547AC"
  success: "#60BB55"
  success-tint: "#60BB551A"
  success-bg: "#D6F9EE"
  warning-gold: "#C37F3B"
  warning-tint: "#EAC5551A"
  danger: "#ED7881"
  danger-strong: "#F12F2F"
  danger-tint: "#FEF2F2"
  accent-purple: "#5E317A"
  neutral-surface: "#F8F9FD"
  neutral-page: "#FBFBFD"
  neutral-divider: "#F1F1F1"
  neutral-border: "#CED4DA"
  text-strong: "#333333"
  text-muted: "#828EA5"
  white: "#FFFFFF"
typography:
  display:
    fontFamily: "Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif"
    fontSize: "24px"
    fontWeight: 700
    lineHeight: "42px"
    letterSpacing: "normal"
  title:
    fontFamily: "Inter, system-ui, sans-serif"
    fontSize: "22px"
    fontWeight: 600
    lineHeight: "1.3"
  body:
    fontFamily: "Inter, system-ui, sans-serif"
    fontSize: "14px"
    fontWeight: 400
    lineHeight: "1.5"
  label:
    fontFamily: "Inter, system-ui, sans-serif"
    fontSize: "12px"
    fontWeight: 600
    lineHeight: "1.4"
rounded:
  sm: "5px"
  md: "6px"
  lg: "12px"
  card: "30px"
  pill: "100px"
  signature: "50px"
spacing:
  xs: "5px"
  sm: "12px"
  md: "14px"
  lg: "26px"
  xl: "32px"
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.white}"
    rounded: "{rounded.sm}"
    padding: "10px 10px"
  button-primary-hover:
    backgroundColor: "{colors.primary-deep}"
    textColor: "{colors.white}"
  button-edit:
    backgroundColor: "{colors.primary-tint}"
    textColor: "{colors.primary}"
    rounded: "{rounded.sm}"
  button-sync:
    backgroundColor: "{colors.success-tint}"
    textColor: "{colors.success}"
    rounded: "{rounded.sm}"
  button-delete:
    backgroundColor: "{colors.danger-tint}"
    textColor: "{colors.danger}"
    rounded: "{rounded.sm}"
  nav-item-active:
    backgroundColor: "{colors.primary-tint}"
    textColor: "{colors.primary}"
    rounded: "{rounded.md}"
    padding: "14px 25px"
  search-input:
    backgroundColor: "{colors.neutral-surface}"
    textColor: "{colors.text-muted}"
    rounded: "{rounded.pill}"
    padding: "0 20px"
    height: "40px"
---

# Design System: Acneno

## 1. Overview

**Creative North Star: "The Calm Control Desk"**

Acneno is a control desk an HR admin sits at for hours: attendance grids, leave queues, expense reconciliation, all dense, all needing fast recognition. The system's job is to stay quiet under that load. Surfaces are pale and flat at rest; the single blue voice marks only what is actionable or selected; status meaning is carried by shape and label as much as by color. Nothing decorates; everything reports.

The aesthetic is professional without stiffness, warm without play, in the lane of Linear (dense information with restraint) and Cal.com (friendly modern business software). It is built for founder-led and owner-operated Indonesian SMBs who need serious operational software, not enterprise heaviness. It must also survive a second life as a thumb-driven employee app on a low-end Android in direct sunlight, so contrast and tap targets are never sacrificed for elegance.

This system explicitly rejects the feel of SAP, Indonesian government portals, and generic Bootstrap admin templates: grey cramped bureaucracy, loud purple gradients, heavy drop shadows, generic chart-card dashboards, and the interchangeable templated "AI dashboard" look. It never gamifies or leaderboards attendance or performance; dignity outranks engagement.

**Key Characteristics:**
- One blue voice (`#1255CC`) for action and selection; everything else is neutral or semantic status.
- Flat pale surfaces (`#FBFBFD` / `#F8F9FD`); soft shadow only as a hover response.
- Status encoded by shape + label + color, never color alone.
- Tinted-pair interactive vocabulary: 10% tint at rest, solid fill on hover.
- A signature asymmetric corner cut where the app shell meets content.

## 2. Colors

A pale near-white canvas, one disciplined blue voice, and a four-role semantic status set; everything tinted toward the brand hue, never flat grey.

### Primary
- **Control Blue** (`#1255CC`): The one voice. Primary buttons, active nav item, links, current selection, focus. Never used as decoration or as a fill behind passive content.
- **Control Blue 10%** (`#1155CC1A`): Rest state for actionable affordances (edit buttons, active nav background). The tint says "you can act here" without shouting.
- **Deep Navy** (`#0A3277`): Hover/pressed depth for Control Blue. The only place blue darkens.

### Secondary
- **Heading Navy** (`#0D1458`): Page headings, section titles, ledger values. Reads as authoritative text, not as an accent.
- **Card Indigo** (`#3547AC`): Reserved for the balance/summary card surface and inbound (income) figures.

### Tertiary
- **Signal Green** (`#60BB55`): Success, sync, positive delta. Tint `#60BB551A` at rest; mint `#D6F9EE` for positive-delta chips.
- **Signal Gold** (`#C37F3B`): Warning, pending, in-progress. Tint `#EAC5551A` at rest. A warm brown-gold, not yellow.
- **Signal Red** (`#ED7881` soft / `#F12F2F` strong): Destructive actions use the soft red on `#FEF2F2`; outbound (expense) figures use the strong red. Reserve strong red for true negatives.
- **Quiet Purple** (`#5E317A`): A single legacy outline-action accent. Use sparingly; do not let it grow into a second voice.

### Neutral
- **Page** (`#FBFBFD`): The dashboard canvas behind everything.
- **Surface** (`#F8F9FD`): Inset fields, search, history tiles, panels that sit on the page.
- **Divider** (`#F1F1F1`): Hairline separators and the sidebar edge.
- **Border** (`#CED4DA`): Form-control strokes.
- **Text Strong** (`#333333`): Default body and dense-table text. Never `#000`.
- **Text Muted** (`#828EA5`): Secondary text, placeholders, metadata.
- **White** (`#FFFFFF`): Sidebar and card surfaces. Tint slightly toward the page color rather than pure white where a large field appears.

### Named Rules
**The One Voice Rule.** Control Blue marks action and selection only. If a screen has blue on more than its actionable affordances and current selection, the rarity is gone and so is the scannability.

**The Shape-Plus-Color Rule.** Every status (present, late, leave, pending, approved, rejected) must carry a label and/or icon shape in addition to its color. Color alone is forbidden for status. Validate against color-vision-deficiency simulation before shipping any status surface.

**The Dignity Rule.** Sensitive HR values (leave reason, performance, health) are never color-coded in a way that ranks or shames a person across a shared view. No red-for-bad-employee.

## 3. Typography

**Body Font:** Inter (with `-apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif`)
**Label/Mono Font:** Inter, same family, weight-differentiated.

**Character:** One humanist sans carries everything: headings, dense table cells, buttons, labels, body. Poppins appears commented out in the source and is legacy; do not reintroduce a second family. Fixed px scale, not fluid clamps, because users read at consistent DPI and a fluid heading in a sidebar looks worse.

### Hierarchy
- **Display / Page heading** (700, 24px, line-height 42px, `#0D1458`): The `header h3`. One per screen.
- **Title** (600, 22px, `#333333`): Sidebar title, panel headings.
- **Subtitle** (600, 18-20px): Card and section headers (`information h5`).
- **Body** (400, 14px, line-height 1.5, `#333333`): Default text and table cells. Prose caps at 65-75ch; data tables may run denser (120ch+ is fine).
- **Label** (600, 12-13px, `#000`/`#828EA5`): Nav section labels, menu items, metadata. Weight, not size, carries emphasis here.

### Named Rules
**The Weight-Not-Size Rule.** At label scale (12-13px), emphasis comes from weight (400 to 600), never from growing the type or adding color. Keeps dense nav and tables calm.

## 4. Elevation

Flat by default. Surfaces sit on the page with no resting shadow; depth is conveyed by the pale tonal step from page (`#FBFBFD`) to surface (`#F8F9FD`) to white. Shadow is a *response to state*, not a property of a component at rest. This deliberately rejects the heavy-shadow Bootstrap-admin look named in the anti-references.

### Shadow Vocabulary
- **Hover lift** (`box-shadow: rgba(149, 157, 165, 0.2) 0px 8px 24px`): Diffuse, neutral, appears only on hover of an interactive card or row. A stronger `0.8` alpha variant exists for emphatic hover; prefer the `0.2`.
- **Button press** (`box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.07)`): Faint, for pressed/active buttons only.
- **Feature card glow** (`box-shadow: rgba(53, 71, 172, 0.5) 0px 20px 50px`): Reserved exclusively for the single summary/balance card, colored to its indigo. Never reuse this as a generic card shadow.

### Named Rules
**The Flat-At-Rest Rule.** No surface carries a drop shadow at rest. If a card needs separation while idle, use the tonal step or a `#F1F1F1` hairline, not a shadow.

## 5. Components

Refined and restrained: every interactive element reads as a calm state, not a loud action. The tinted-pair pattern is the spine of the whole component vocabulary.

### Buttons
- **Shape:** Gently rounded, 5px (`btn-act`) to 6px. Pills (`100px`) only for search and filter chips, not standard buttons.
- **Primary:** Control Blue (`#1255CC`) fill, white text, `padding: 10px`. Hover deepens to Deep Navy (`#0A3277`).
- **Tinted-pair semantics (the signature pattern):** edit (blue), sync (green `#60BB55`), warning (gold `#C37F3B`), delete (red `#ED7881`). Each renders at 10% tint with a colored border at rest, then fills solid on hover. The rest state is quiet; intent is legible by hue and icon before the user commits.
- **Pagination:** White with blue border at rest, solid blue when active or hovered.

### Search / Inputs
- **Search:** Pill (`100px`), borderless, `#F8F9FD` fill, muted text, 40px tall, 0 20px padding. Companion `btn-search` is a `#DCE2F4` circle.
- **Fields:** `#CED4DA` stroke, white or `#F8F9FD` fill, ~6px radius. Focus shifts the border to Control Blue; do not add a glow. Always pair every control with a visible label (the data is dense and CVD-safe).

### Cards / Containers
- **Corner Style:** 30px (`information .card`); pills and 12px elsewhere. Nested cards are forbidden.
- **Background:** White or `#F8F9FD` on the `#FBFBFD` page.
- **Shadow Strategy:** Flat at rest (see Elevation). The colored indigo glow belongs to the single summary card only.
- **Internal Padding:** 30px for feature cards, 32px for the content body.

### Navigation
- **Sidebar:** Fixed, 268px, white, hairline `#F1F1F1` right edge. Section labels at 12px/600. Items at 12px/400.
- **States:** Active item = Control Blue text on `#1155CC1A` tint, weight 600, 6px radius. Hover = `#F0F0F0` neutral tint, no color shift. Icons swap inactive/active SVG pairs in lockstep with the active state. Mobile: offcanvas drawer (Bootstrap), white, full-height.

### Signature: The Corner Cut
The app shell meets content with a large asymmetric radius: `.content` uses `border-radius: 50px 0 0 50px` and `.div-dashboard` cuts `100px` at top-left and bottom-right only. This single architectural gesture is the brand's one flourish; keep it to the shell seam and the login/dashboard panel. Do not sprinkle 100px radii onto ordinary cards.

## 6. Do's and Don'ts

### Do:
- **Do** reserve Control Blue (`#1255CC`) for action and selection only (The One Voice Rule).
- **Do** encode every status with shape + label in addition to color, and test under CVD simulation.
- **Do** keep surfaces flat at rest; introduce the `rgba(149,157,165,0.2)` shadow only on hover.
- **Do** use the tinted-pair button pattern (10% tint at rest, solid on hover) for all semantic actions.
- **Do** build attendance/status grids as real `<table>` with row and column headers, plus an optional companion list view for linear reading.
- **Do** keep employee-facing flows high-contrast, thumb-sized, and resilient on Android 10 / 4GB / 3G.
- **Do** respect Indonesian workplace rhythm (Friday prayer, Ramadan hours, Idul Fitri) in scheduling surfaces rather than forcing generic status models.

### Don't:
- **Don't** use loud purple gradients, heavy drop shadows, or generic chart-card dashboards (named anti-references).
- **Don't** let it feel like SAP, an Indonesian government portal, or a generic Bootstrap admin template: grey, cramped, hostile bureaucracy.
- **Don't** ship the interchangeable templated "AI dashboard" aesthetic.
- **Don't** gamify or leaderboard attendance or performance; never rank people by color.
- **Don't** rely on color alone for any status.
- **Don't** use `border-left`/`border-right` greater than 1px as a colored accent stripe (a commented-out `#3547ac` left stripe exists in `nav-sidebar.css`; keep it dead).
- **Don't** use `#000` or `#fff` for text and large fields; use `#333333` and tinted neutrals.
- **Don't** introduce a second type family; Inter only. Poppins is legacy.
- **Don't** load three icon sets. Font Awesome, Bootstrap Icons, and Material Design Icons are all linked today; pick one and drop the rest for consistency and payload.
- **Don't** spread the 100px signature radius onto ordinary cards; it belongs to the shell seam only.
- **Don't** wrap everything in a card. Most dense data wants a table, not a card grid.
