# Web Frontend & Views

The current frontend is **server-rendered PHP views + jQuery AJAX**. For the API+SPA target this is the layer being replaced — so this doc is an inventory of *what exists* and *what state it's in for API-fication*, not a spec to preserve. Pair with `DESIGN.md` (the visual system the new SPA should follow) and `03-api-surface.md` (what backend already speaks JSON).

## Master templates

- **`application/views/Template.php`** — public/unauthenticated shell (login, signup). Bootstrap 5.2.3 CDN, jQuery 3.6.1, inline form styling, `jquery.toast`.
- **`application/views/TemplateDashboard.php`** (~2,200 lines) — the authenticated app shell. Contains the full nav/sidebar, loads the entire frontend library stack, and injects per-page content via `<?= $content ?>`. **This single file is the de-facto "frontend framework"** of the app.
- **`application/views/Login.php`**, **`Signup.php`** — auth screens.
- View-load pattern in controllers:
  ```php
  $data['content'] = $this->load->view('module/view_name', $data, true);
  $this->load->view('TemplateDashboard', $data);
  ```

## View directory map (`application/views/`)

~55 module directories, by domain:

| Domain | View dirs |
|---|---|
| HRMS | `attendance/`, `leave/`, `overtime/`, `approvals/`, `admin/` (leave-types, quotas, holidays, attendance-settings, performance, offices), `position/`, `roles/`, `modules/`, `user/`, `profile/` |
| Marketplace/Ops | `transaction/`, `transaction_item/`, `marketplace/`, `marketplace_account/`, `meta_account/`, `product/`, `product_3rd/`, `stock/`, `shipping/`, `discount/`, `customer/`, `operasional/`, `label/` |
| Marketing | `endorse/` (~27 files), `endorse_campaign/` (~10), `influencer/`, `influencer_dummy/`, `review_endorse/`, `overview/`, `scraper/`, `codeboost/`, `admin_fee_configuration/` |
| Finance | `dashboard/` (15+ variants), `expense/`, `report/` |
| Engagement/CRM | `quest/`, `quest_level/`, `crm/`, `group_wa/`, `milestone/`, `interview/`, `recruitment/`, `benefit/`, `notifications/`, `testimoni/` |
| System | `errors/` (incl. `html/error_403`), `public/` (incl. `hrms_swagger`), `redirect/`, `utils/`, `welcome_message.php`, `loading.php`, `loading_v2.php` |

## Frontend stack (loaded by `TemplateDashboard.php`)

| Category | Libraries |
|---|---|
| Base | Bootstrap 5.2.3 (CDN + local), jQuery 3.6.0/3.6.1, Popper |
| Tables | **ag-grid-community** (enterprise-style data grids — heavily used) |
| Charts | Chart.js + datalabels plugin, `gauge.min.js`, custom `donut_chart.js`/`line_chart.js` |
| Visualization | **D3.js v7** — career tree, org chart, dendrogram, clustering (`career-tree-visualization.js`, `org-chart-visualization.js`, `career-dendrogram.js`, `career-clustering.js`) |
| Date/time | Moment.js, Daterangepicker, Flatpickr, FullCalendar |
| Form controls | Select2, Bootstrap datepicker, Tagify |
| Feedback | SweetAlert2 (modals/confirm), Toastr (toasts) |
| Media | Plyr (video), Lightbox, Lightslider, Owl Carousel |
| Device | html5-qrcode (resi/QR scanning for shipping flows) |
| Realtime | Firebase Realtime DB SDK (present but **commented out**) |
| Misc | Prism (code highlight), Readmore |

Custom JS lives in `assets/js/` (`custom.js` = shared form/AJAX handlers) and `assets/css/` (`style.css`, `attendance-report.css`, `career-tree.css`, `arrangement-editor.css`, `nav-sidebar.css`, etc.). CSS is loaded with `?v=` cache-busting query params.

## AJAX patterns (the contract that isn't)

Dominant pattern in `assets/js/custom.js` and inline view scripts:
```js
$("#form").submit(function () {
  var data = new FormData(this);
  $.ajax({
    type: "POST", url: form.attr("action"), data,
    cache: false, contentType: false, processData: false,
    success: function (response) { /* checks if response contains "success" */ },
    error: function (xhr) { /* show message */ }
  });
  return false;
});
```
- Submissions are `multipart/form-data` (supports file upload inline).
- **Success is detected by substring-matching `"success"` in an HTML response** — there is no stable JSON envelope for most web modules.
- Data loads use `GET` to `site_url()` routes returning HTML table fragments rendered server-side (often via the `Template` lib's pagination helpers).

## SPA-readiness assessment

| Tier | Modules | Notes |
|---|---|---|
| **Already JSON-backed** (low effort) | Employee + approver HRMS flows (`Api_hrms`), performance (`Api_performance`) | A SPA/mobile client already consumes these. Spec in `docs/openapi/hrms.yaml`. |
| **Partial / ad-hoc JSON** | Attendance report (`data_json`), approvals web AJAX, endorse queue endpoints | Return JSON-ish or HTML; need normalizing to a consistent envelope. |
| **Pure server-render** (high effort) | All admin CRUD pages, marketplace (`Transaction`, `Product`, `Stock`, `Shipping`, `Marketplace*`), marketing (`Endorse`, `Influencer`, `Overview`), finance (`Dashboard`, `Expense`, `Report`) | Logic lives in controllers + the `Template` god-lib. **Must build new JSON endpoints**; business rules extracted per `05-features.md`. |

### Coupling to break for SPA
- **Session identity** — `BaseController` derives `user_id` from `$_SESSION['user']` and gates every page. SPA needs stateless JWT identity end-to-end (today JWT only exists for `Api_hrms`).
- **Permission-in-controller** — RBAC enforced in the controller constructor; must become API middleware/policies.
- **HTML-fragment responses** — replace substring-`"success"` contract with JSON envelopes + proper status codes.
- **`Template` library rendering helpers** (pagination, formatting, alerts) — presentation concerns mixed with data; the data shaping must move server-side into API responses, formatting to the SPA.
- **ag-grid / D3 / Chart.js config** currently built in PHP-rendered `<script>` blocks — these become SPA components fed by API JSON.
