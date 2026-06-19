# RN Notification Deep-Link Handoff

How to route a tapped notification (push or in-app bell) to the correct screen for **leave**
and **overtime**. Covers the payload contract, the owner-vs-approver routing rule, the exact
endpoints, and real example responses.

Backend is live on `https://acnenosystem.com`. Auth = `Authorization: Bearer <accessToken>` (the
JWT from `POST /api/hrms/auth/login`). See `MOBILE-HANDOFF.md` for auth + device registration.

---

## 1. The routing rule (read this first)

A notification's `data` tells you **who the recipient is relative to the request**, which decides
the screen:

- **`data.step_id` present** → recipient is an **approver**. Open the **approval detail** screen
  using `step_id`.
- **`data.step_id` absent** → recipient is the **requester/owner**. Open the **request detail**
  screen using `related_id`.

```ts
function routeFromNotification(data: Record<string,string>) {
  const { related_table, related_id, step_id } = data;
  if (step_id) {
    // approver flow — approval detail is keyed by the approval STEP id
    return related_table === 'overtime_requests'
      ? `/api/hrms/overtime/approvals/${step_id}`
      : `/api/hrms/leave/approvals/${step_id}`;
  }
  // owner flow — request detail is keyed by the request id
  return related_table === 'overtime_requests'
    ? `/api/hrms/overtime/${related_id}`
    : `/api/hrms/leave/${related_id}`;
}
```

> All `data` values are **strings** (FCM v1 requirement), e.g. `related_id: "126"`, `step_id: "225"`.

---

## 2. Why (the gotcha this solves)

- `GET /api/hrms/leave/{id}` and `GET /api/hrms/overtime/{id}` are **owner-scoped** — they `403`
  for anyone who isn't the requester. An approver tapping a "please approve" push must **not** use
  these.
- The approver screen is `GET /api/hrms/leave/approvals/{stepId}` (and overtime equivalent), keyed
  by the **approval-step id**, not the request id.
- So approver notifications now carry **`step_id`** (the `approval_steps.id`). Requester
  notifications carry only `related_id` (the request id). `related_table` + `related_id` always
  identify the underlying entity; `step_id` is the extra key for the approver screen.

---

## 3. Endpoint map

| Recipient | Notification events | data has | Open |
|-----------|--------------------|----------|------|
| Approver | `leave.approver_assigned`, `overtime.approver_assigned` | `step_id` (+ `related_id`) | `GET /api/hrms/{leave|overtime}/approvals/{step_id}` |
| Requester | `leave.approved/rejected/...`, `overtime.approved/rejected/...` | `related_id` only | `GET /api/hrms/{leave|overtime}/{related_id}` |

Approver actions from the detail screen:
- `POST /api/hrms/leave/approvals/{stepId}/approve` · `.../reject`
- `POST /api/hrms/overtime/approvals/{stepId}/approve` · `.../reject`

---

## 4. Payload examples (real data)

### 4a. Approver — `leave.approver_assigned`
Push received:
```json
{
  "notification": {
    "title": "Pengajuan Cuti Baru Perlu Disetujui",
    "body": "Jhon Doe mengajukan Annual Leave (2 hari) dari 19 Jun 2026 sampai 20 Jun 2026. Silakan review dan berikan persetujuan."
  },
  "data": {
    "type": "info",
    "related_table": "leave_requests",
    "related_id": "126",
    "step_id": "225"
  }
}
```
Tap → `GET /api/hrms/leave/approvals/225` → **200**:
```json
{
  "step": { "id": 225, "stepNo": 1, "stepName": "Admin", "action": "PENDING", "canTakeAction": true },
  "request": {
    "id": 126, "requestNo": "LV-20260619-3358",
    "leaveTypeName": "Annual Leave", "startDate": "2026-06-19", "endDate": "2026-06-20",
    "daysCount": 2, "reason": "Cuti aja", "status": "Pending", "statusRaw": "IN_REVIEW"
  },
  "requester": { "id": 50, "name": "Jhon Doe", "email": "jhondoe@gmail.com", "positionName": "example position" },
  "workflow": {
    "currentStep": 1, "totalSteps": 1, "status": "IN_PROGRESS",
    "steps": [{ "id": 225, "stepNo": 1, "assignedApproverId": 4, "assignedApproverName": "Alvin Dimas Satria", "action": "PENDING" }]
  }
}
```

### 4b. Approver — `overtime.approver_assigned`
```json
{
  "notification": {
    "title": "Pengajuan Lembur Baru Perlu Disetujui",
    "body": "Jhon Doe mengajukan lembur Lembur Akhir Pekan pada tanggal 19 Jun 2026 (08:00:00 - 13:00:00, 5.00 jam). Silakan review dan berikan persetujuan."
  },
  "data": {
    "type": "info",
    "related_table": "overtime_requests",
    "related_id": "13",
    "step_id": "24"
  }
}
```
Tap → `GET /api/hrms/overtime/approvals/24`.

### 4c. Requester — e.g. `leave.approved` (no `step_id`)
```json
{
  "notification": { "title": "Pengajuan Cuti Disetujui", "body": "Pengajuan Annual Leave Anda ... telah disetujui." },
  "data": { "type": "success", "related_table": "leave_requests", "related_id": "126" }
}
```
Tap → `GET /api/hrms/leave/126` (owner-scoped; the requester is the owner, so 200).

---

## 5. In-app bell

The in-app notification list (`GET /api/hrms/...` notification feed) returns rows like:
```json
{
  "id": 259, "title": "Pengajuan Cuti Baru Perlu Disetujui",
  "message": "Jhon Doe mengajukan Annual Leave (2 hari) ...",
  "type": "info", "related_table": "leave_requests", "related_id": 126, "is_read": 0
}
```
Note: in the **in-app** row `related_id` is an **int**; the bell carries the **request id** only
(no `step_id`). For the in-app approver case, resolve the step the same way the screen does, or open
the approvals inbox (`GET /api/hrms/leave/approvals`) and match `leaveRequestId == related_id`. The
**push** path is the one that carries `step_id` for a one-tap deep-link.

---

## 6. Foreground vs background (iOS/Android)

- **Background / killed**: the `notification` block shows in the tray automatically. On tap, read
  `data` and route per §1.
- **Foreground**: the OS does NOT auto-display — handle the message in-app (`onMessage`) and render
  your own banner using `notification` + `data`, then route on tap.
- `data.type` (`info` | `success` | `warning` | `error`) drives styling.

---

## 7. Quick test checklist
- [ ] Approver push → `data.step_id` present → `…/approvals/{step_id}` returns 200 with `step` +
      `request` + `requester` + `workflow`.
- [ ] Requester push → no `step_id` → `…/{related_id}` returns 200 for the owner.
- [ ] Approver must NOT call `…/{related_id}` (returns 403 owner-scoped).
- [ ] `canTakeAction` in the step tells you whether to show approve/reject buttons (false once the
      step is already actioned).
