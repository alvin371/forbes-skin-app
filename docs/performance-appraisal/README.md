# Performance Appraisal API

Performance Appraisal is role-based. Templates can be global (no role) or scoped to a role. The main flow is:
1. Dashboard user creates a template.
2. Dashboard user adds items and saves the template.
3. Active template is visible in mobile for employees with the matching role.
4. Mobile user fills items and submits a submission.
5. Dashboard user views submissions.

## Auth and permissions
- Admin endpoints require an authenticated session and `performance_admin` permission.
- HRMS mobile endpoints use the HRMS auth flow (`Api_hrms::require_user`).
- HRMS auth/profile responses include `role_id` and `role_name`.

## Base routes
- Dashboard/Admin API: `/admin/performance/*`
- HRMS Mobile API: `/api/hrms/performance/*`

## Roles (dashboard/admin)
`GET /admin/performance/roles`

Response (200):
```json
{
  "success": true,
  "data": [
    { "id": 3, "name": "sales", "display_name": "Sales" }
  ]
}
```

## Templates (dashboard/admin)

### List templates
`GET /admin/performance/templates`

Query params:
- `period_year` (number)
- `role_id` (number, optional) - preferred
- `department` (string, legacy)
- `is_active` (0|1)

Response (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 12,
      "name": "Sales Performance 2026",
      "period_year": 2026,
      "role_id": 3,
      "department": null,
      "is_active": 1,
      "created_at": "2025-01-10 09:21:33",
      "updated_at": "2025-01-10 09:22:10",
      "role_display_name": "Sales",
      "item_count": 5,
      "total_weight": 100,
      "submission_count": 12
    }
  ]
}
```

### Get template detail
`GET /admin/performance/templates/{id}`

Response (200):
```json
{
  "success": true,
  "data": {
    "id": 12,
    "name": "Sales Performance 2026",
    "period_year": 2026,
    "role_id": 3,
    "is_active": 1,
    "item_count": 5,
    "total_weight": 100,
    "items": [
      {
        "id": 88,
        "template_id": 12,
        "order_no": 1,
        "objective": "Achieve sales target",
        "kpi": "Total revenue generated",
        "target_value": 100,
        "unit": "%",
        "weight": 25,
        "created_at": "2025-01-10 09:25:10",
        "updated_at": "2025-01-10 09:25:10"
      }
    ]
  }
}
```

### Create template
`POST /admin/performance/templates`

Body:
```json
{
  "name": "Sales Performance 2026",
  "period_year": 2026,
  "role_id": 3,
  "is_active": 0
}
```

Response (200):
```json
{ "success": true, "id": 12, "message": "Template created successfully" }
```

### Update template
`PUT /admin/performance/templates/{id}`

Body (partial):
```json
{
  "name": "Sales Performance 2026",
  "period_year": 2026,
  "role_id": 3,
  "is_active": 1
}
```

Notes:
- Activating requires at least 1 item and total item weight = 100.

Response (200):
```json
{ "success": true, "message": "Template updated successfully" }
```

### Delete template
`DELETE /admin/performance/templates/{id}`

Notes:
- Deletion fails if submissions exist for the template.

Response (200):
```json
{ "success": true, "message": "Template deleted successfully" }
```

## Template items (dashboard/admin)

### Create item
`POST /admin/performance/templates/{id}/items`

Body:
```json
{
  "order_no": 1,
  "objective": "Achieve sales target",
  "kpi": "Total revenue generated",
  "target_value": 100,
  "unit": "%",
  "weight": 25
}
```

Response (200):
```json
{ "success": true, "id": 88, "message": "Item created successfully" }
```

Validation:
- `target_value` must be > 0.
- `weight` must be >= 0.

### Update item
`PUT /admin/performance/items/{id}`

Body (partial):
```json
{ "weight": 30 }
```

Response (200):
```json
{ "success": true, "message": "Item updated successfully" }
```

### Delete item
`DELETE /admin/performance/items/{id}`

Response (200):
```json
{ "success": true, "message": "Item deleted successfully" }
```

### Reorder items
`POST /admin/performance/templates/{id}/items/reorder`

Body:
```json
{
  "items": [
    { "id": 88, "order_no": 1 },
    { "id": 89, "order_no": 2 }
  ]
}
```

Response (200):
```json
{ "success": true, "message": "Items reordered successfully" }
```

## Mobile endpoints (HRMS)

### Auth/profile includes role
`POST /api/hrms/auth/login` and `GET /api/hrms/profile`

Response fields:
```json
{
  "id": 8,
  "name": "Jane Doe",
  "email": "jane@example.com",
  "role": "Employee",
  "role_id": 3,
  "role_name": "Sales"
}
```

### Get active template by user role
`GET /api/hrms/performance/templates/active?period_year=2026`

Response (200):
```json
{
  "data": {
    "id": 12,
    "name": "Sales Performance 2026",
    "period_year": 2026,
    "role_id": 3,
    "role_display_name": "Sales",
    "employee_role_id": 3,
    "employee_role_name": "Sales",
    "items": [
      { "id": 88, "objective": "Achieve sales target", "target_value": 100, "weight": 25 }
    ]
  }
}
```

### Create submission
`POST /api/hrms/performance/submissions`

Body:
```json
{
  "template_id": 12,
  "items": [
    { "template_item_id": 88, "actual_value": 120 },
    { "template_item_id": 89, "actual_value": 95 }
  ]
}
```

Response (201):
```json
{ "id": 501, "total_score": 112.5, "message": "Submission created." }
```

Validation:
- One submission per employee, template, and period.
- `actual_value` must be >= 0.

### List my submissions
`GET /api/hrms/performance/submissions`

Query params:
- `period_year` (optional)

Response (200):
```json
{
  "data": [
    {
      "id": 501,
      "template_id": 12,
      "template_name": "Sales Performance 2026",
      "period_year": 2026,
      "status": "SUBMITTED",
      "total_score": 112.5
    }
  ]
}
```

### Get submission detail
`GET /api/hrms/performance/submissions/{id}`

Response (200):
```json
{
  "data": {
    "id": 501,
    "template_id": 12,
    "template_name": "Sales Performance 2026",
    "period_year": 2026,
    "status": "SUBMITTED",
    "total_score": 112.5,
    "items": [
      {
        "template_item_id": 88,
        "objective": "Achieve sales target",
        "target_value": 100,
        "actual_value": 120,
        "weight": 25,
        "score_ratio": 1.2,
        "final_score": 30
      }
    ]
  }
}
```

## Dashboard submissions

### List submissions
`GET /admin/performance/submissions`

Query params:
- `template_id` (number)
- `period_year` (number)
- `role_id` (number, preferred)
- `department` (string, legacy)

Response (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 501,
      "template_id": 12,
      "template_name": "Sales Performance 2026",
      "template_role_id": 3,
      "template_role_name": "Sales",
      "employee_id": 8,
      "employee_name": "Jane Doe",
      "employee_role_id": 3,
      "employee_role_name": "Sales",
      "period_year": 2026,
      "status": "SUBMITTED",
      "total_score": 112.5
    }
  ]
}
```

## Error formats
- Dashboard/Admin API: `{ "success": false, "error": "Message" }`
- HRMS API: `{ "message": "Message" }`
