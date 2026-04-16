# HRMS API (Mobile) Documentation

Swagger/OpenAPI source of truth:
- Swagger UI: `/docs/hrms`
- OpenAPI YAML: `/docs/openapi/hrms.yaml`

Base URL: `/api/hrms`

Auth:
- Use `Authorization: Bearer <accessToken>` for protected endpoints.
- Tokens are issued by `/auth/login` and refreshed by `/auth/refresh`.

## Auth

### POST /auth/login
Request:
```json
{
  "email": "user@example.com",
  "password": "secret"
}
```
Response 200:
```json
{
  "accessToken": "jwt-token",
  "refreshToken": "refresh-token",
  "user": {
    "id": 123,
    "name": "Jane Doe",
    "email": "user@example.com",
    "role": "HR",
    "role_id": 7,
    "role_name": "Human Resources",
    "position_id": 15,
    "position_name": "Senior Recruiter",
    "schedule": {
      "start_time": "08:00",
      "end_time": "17:00",
      "source": "office",
      "special_schedule": false
    }
  }
}
```

### POST /auth/refresh
Request:
```json
{
  "refreshToken": "refresh-token"
}
```
Response 200:
```json
{
  "accessToken": "new-jwt-token",
  "refreshToken": "new-refresh-token"
}
```

## Profile

### GET /profile
Response 200:
```json
{
  "id": 123,
  "name": "Jane Doe",
  "email": "user@example.com",
  "role": "HR",
  "role_id": 7,
  "role_name": "Human Resources",
  "position_id": 15,
  "position_name": "Senior Recruiter",
  "schedule": {
    "start_time": "08:00",
    "end_time": "17:00",
    "source": "office",
    "special_schedule": false
  }
}
```

### PATCH /profile
Request:
```json
{
  "name": "Jane Updated",
  "email": "jane.updated@example.com",
  "phone_number": "+62812345678"
}
```
Response 200:
```json
{
  "id": 123,
  "name": "Jane Updated",
  "email": "jane.updated@example.com",
  "role": "HR",
  "role_id": 7,
  "role_name": "Human Resources",
  "position_id": 15,
  "position_name": "Senior Recruiter",
  "schedule": {
    "start_time": "08:00",
    "end_time": "17:00",
    "source": "office",
    "special_schedule": false
  }
}
```

## PIN

### POST /pin/setup
Request:
```json
{
  "pin": "123456"
}
```
Response 200:
```json
{
  "ok": true
}
```

### POST /pin/verify
Request:
```json
{
  "pin": "123456"
}
```
Response 200 (ok):
```json
{
  "ok": true
}
```
Response 200 (locked):
```json
{
  "ok": false,
  "locked": true,
  "lockedUntil": "2025-01-01 12:30:00"
}
```

### POST /pin/reset
Request:
```json
{
  "password": "current-password"
}
```
Response 200:
```json
{
  "ok": true
}
```

## Config

### GET /config
Response 200:
```json
{
  "office": {
    "id": 1,
    "name": "HQ Office",
    "lat": -6.2000000,
    "lng": 106.8166667,
    "radius_m": 150,
    "min_accuracy_m": 50,
    "allowed_ip_cidrs": "203.0.113.0/24"
  },
  "attendance": {
    "min_accuracy_m": 50,
    "radius_m": 150,
    "requires_ip": true,
    "response_times": [
      "08:00",
      "17:00"
    ],
    "history_days": 30,
    "recap_months": 6
  },
  "wifi": {
    "allowed_bssids": [
      "aa:bb:cc:dd:ee:ff",
      "11:22:33:44:55:66"
    ],
    "allowed_ssids": [
      "OfficeWifi-1",
      "OfficeWifi-2"
    ]
  },
  "offices": [
    {
      "office": {
        "id": 1,
        "name": "HQ Office",
        "lat": -6.2000000,
        "lng": 106.8166667,
        "radius_m": 150,
        "min_accuracy_m": 50,
        "allowed_ip_cidrs": "203.0.113.0/24"
      },
      "attendance": {
        "min_accuracy_m": 50,
        "radius_m": 150,
        "requires_ip": true,
        "response_times": [
          "08:00",
          "17:00"
        ],
        "history_days": 30,
        "recap_months": 6
      },
      "wifi": {
        "allowed_bssids": [
          "aa:bb:cc:dd:ee:ff",
          "11:22:33:44:55:66"
        ],
        "allowed_ssids": [
          "OfficeWifi-1",
          "OfficeWifi-2"
        ]
      }
    }
  ]
}
```
Notes:
- `allowed_ssids`, `attendance_response_times`, `attendance_history_days`, and `attendance_recap_months` are read from the active row in `offices`.
- Every entry in `offices` includes `attendance.radius_m` and `attendance.min_accuracy_m`.
- If both `allowed_bssids` and `allowed_ssids` are set, the WiFi proof must match both.

## Attendance

Notes:
- Attendance has two categories:
  - `REGULAR` / `Kantor`: normal office attendance with geofence/IP/WiFi validation.
  - `OUT_OF_TOWN` / `Dinas Luar Kota`: GPS is still captured, but office radius/IP/WiFi checks are skipped.
- `Dinas Luar Kota` attendance requires a fresh uploaded photo for each `IN` and `OUT`.
- For out-of-town attendance, the request must send `attachment_path` using the `storedPath` returned by `POST /upload` with `type=attendance`.

### POST /attendance/office-proof
Request:
```json
{
  "wifiProof": {
    "bssid": "aa:bb:cc:dd:ee:ff",
    "ssid": "OfficeWifi-1"
  }
}
```
Response 200:
```json
{
  "ok": true
}
```

### GET /attendance/status
Query:
- `lat`: float, required
- `lng`: float, required
- `accuracy`: float, required
- `bssid`, `ssid`, `bssids`, `ssids`: optional WiFi proof query params

Response 200:
```json
{
  "office": {
    "id": 1,
    "name": "HQ Office",
    "lat": -6.2000000,
    "lng": 106.8166667,
    "radius_m": 150,
    "min_accuracy_m": 50
  },
  "user": {
    "lat": -6.200123,
    "lng": 106.816789,
    "accuracy": 15
  },
  "computed": {
    "inside_radius": true,
    "distance_m": 120.12,
    "ip_ok": true,
    "can_confirm": true,
    "reasons": []
  },
  "wifi": {
    "has_rules": true,
    "provided": true,
    "ok": true
  },
  "schedule": {
    "start_time": "08:00",
    "end_time": "17:00",
    "late_threshold": "08:15",
    "early_threshold": "16:45",
    "source": "office",
    "is_special": false
  }
}
```

### POST /attendance/check-in
Request:
```json
{
  "lat": -6.200123,
  "lng": 106.816789,
  "gpsAccuracy": 15,
  "wifiProof": {
    "bssid": "aa:bb:cc:dd:ee:ff"
  }
}
```
Response 200:
```json
{
  "ok": true,
  "attendance_log_id": 101,
  "type": "IN",
  "attendanceCategory": "REGULAR",
  "attendanceCategoryLabel": "Kantor",
  "distanceMeters": 120.12,
  "notes": [
    "Late check-in by 20 minutes."
  ],
  "flags": {
    "late": true,
    "early_checkout": false,
    "special_schedule": false
  },
  "minutes": {
    "late": 20,
    "early_checkout": null
  },
  "schedule": {
    "start_time": "08:00",
    "end_time": "17:00",
    "source": "office"
  },
  "office": {
    "id": 1,
    "name": "HQ Office"
  }
}
```

If `flags.late` is `true`, submit the follow-up reason to `POST /attendance/{attendance_log_id}/reason`.

### POST /attendance/check-out
Request:
```json
{
  "lat": -6.200123,
  "lng": 106.816789,
  "gpsAccuracy": 15,
  "wifiProof": {
    "bssid": "aa:bb:cc:dd:ee:ff"
  }
}
```
Response 200:
```json
{
  "ok": true,
  "attendance_log_id": 102,
  "type": "OUT",
  "attendanceCategory": "REGULAR",
  "attendanceCategoryLabel": "Kantor",
  "distanceMeters": 120.12,
  "notes": [
    "Early checkout by 25 minutes."
  ],
  "flags": {
    "late": false,
    "early_checkout": true,
    "special_schedule": false
  },
  "minutes": {
    "late": null,
    "early_checkout": 25
  },
  "schedule": {
    "start_time": "08:00",
    "end_time": "17:00",
    "source": "office"
  },
  "office": {
    "id": 1,
    "name": "HQ Office"
  }
}
```

If `flags.early_checkout` is `true`, submit the follow-up reason to `POST /attendance/{attendance_log_id}/reason`.

### POST /upload
Request: `multipart/form-data`
- `type`: `attendance`, `leave`, or `profile`
- `file`: uploaded file

Attendance upload example:
```bash
curl -X POST "/api/hrms/upload" \
  -H "Authorization: Bearer <accessToken>" \
  -F "type=attendance" \
  -F "file=@proof.jpg"
```

Response 200:
```json
{
  "type": "attendance",
  "path": "api/hrms/files/attendance/api-upload/abc123.jpg",
  "storedPath": "writable/uploads/attendance/api-upload/abc123.jpg",
  "url": "https://example.com/api/hrms/files/attendance/api-upload/abc123.jpg",
  "filename": "abc123.jpg",
  "originalName": "proof.jpg",
  "sizeBytes": 245123
}
```

Use `storedPath` as `attachment_path` when calling `POST /attendance/out-of-town/check-in` or `POST /attendance/out-of-town/check-out`.

### POST /attendance/out-of-town/check-in
Request:
```json
{
  "lat": -7.290110,
  "lng": 112.734210,
  "gpsAccuracy": 18,
  "attachment_path": "writable/uploads/attendance/api-upload/abc123.jpg"
}
```

Response 200:
```json
{
  "ok": true,
  "attendance_log_id": 201,
  "type": "IN",
  "attendanceCategory": "OUT_OF_TOWN",
  "attendanceCategoryLabel": "Dinas Luar Kota",
  "attachmentPath": "https://example.com/api/hrms/files/attendance/api-upload/abc123.jpg",
  "distanceMeters": 12450.33,
  "notes": [
    "Late check-in by 10 minutes."
  ],
  "flags": {
    "late": true,
    "early_checkout": false,
    "special_schedule": false
  },
  "minutes": {
    "late": 10,
    "early_checkout": null
  },
  "schedule": {
    "start_time": "08:00",
    "end_time": "17:00",
    "source": "office"
  },
  "office": {
    "id": 1,
    "name": "HQ Office"
  }
}
```

Notes:
- Photo proof is mandatory.
- A fresh uploaded photo is required for each attendance action.
- Office geofence, IP, and WiFi validation are skipped for this endpoint.

### POST /attendance/out-of-town/check-out
Request:
```json
{
  "lat": -7.290110,
  "lng": 112.734210,
  "gpsAccuracy": 18,
  "attachment_path": "writable/uploads/attendance/api-upload/xyz456.jpg"
}
```

Response 200:
```json
{
  "ok": true,
  "attendance_log_id": 202,
  "type": "OUT",
  "attendanceCategory": "OUT_OF_TOWN",
  "attendanceCategoryLabel": "Dinas Luar Kota",
  "attachmentPath": "https://example.com/api/hrms/files/attendance/api-upload/xyz456.jpg",
  "distanceMeters": 12450.33,
  "notes": [],
  "flags": {
    "late": false,
    "early_checkout": false,
    "special_schedule": false
  },
  "minutes": {
    "late": null,
    "early_checkout": null
  },
  "schedule": {
    "start_time": "08:00",
    "end_time": "17:00",
    "source": "office"
  },
  "office": {
    "id": 1,
    "name": "HQ Office"
  }
}
```

### POST /attendance/{id}/reason
Request JSON body (all fields optional):
```json
{
  "reason": "Traffic was unusually heavy this morning.",
  "attachment_path": "writable/uploads/attendance/api-upload/abc123.jpg"
}
```

- `reason`: string, optional
- `attachment_path`: string, optional — use `storedPath` from `POST /upload` instead of uploading a file inline

Request with file upload: `multipart/form-data`
- `reason`: string, optional
- `attachment`: file, optional

Response 200:
```json
{
  "ok": true,
  "id": 101,
  "type": "IN",
  "reason": "Traffic was unusually heavy this morning.",
  "attachmentPath": "https://example.com/api/hrms/files/attendance/101/proof.png",
  "flags": {
    "late": true,
    "early_checkout": false
  },
  "hasReasonOrAttachment": true
}
```

Notes:
- Reason follow-up is only allowed for late `IN` or early `OUT`.
- For `Dinas Luar Kota`, the original proof photo is locked and cannot be replaced by this endpoint.

### GET /attendance/history
Query:
- `month=YYYY-MM` optional

Response 200:
```json
{
  "month": "2025-01",
  "data": [
    {
      "id": 10,
      "user_id": 123,
      "office_id": 1,
      "type": "IN",
      "lat": -6.2001230,
      "lng": 106.8167890,
      "accuracy": 15,
      "distance_m": 120.12,
      "method": "GEOFENCE+IP+WIFI",
      "ip_address": "203.0.113.10",
      "user_agent": "okhttp/4.x",
      "notes": [
        "Check-in recorded as Dinas Luar Kota."
      ],
      "flags": {
        "late": false,
        "early_checkout": false
      },
      "attendance_reason": null,
      "attachmentPath": "https://example.com/api/hrms/files/attendance/api-upload/abc123.jpg",
      "hasReasonOrAttachment": true,
      "reasonEligible": false,
      "attendanceCategory": "OUT_OF_TOWN",
      "attendanceCategoryLabel": "Dinas Luar Kota",
      "isOutOfTown": true,
      "created_at": "2025-01-01 09:00:00",
      "office_name": "HQ Office"
    }
  ]
}
```

### GET /attendance/recap
Query: `month=YYYY-MM` (optional, defaults to current month)
Response 200:
```json
{
  "month": "2025-01",
  "present_days": 18,
  "late_count": 2,
  "early_checkout_count": 1,
  "absent_count": 1,
  "leave_days": 2,
  "start_time": "08:00",
  "end_time": "17:00",
  "schedule_source": "office",
  "special_schedule": false
}
```

### GET /attendance/recap-all
Admin/HR only.
Query: `month=YYYY-MM`
Response 200:
```json
{
  "month": "2025-01",
  "data": [
    {
      "month": "2025-01",
      "present_days": 18,
      "late_count": 2,
      "early_checkout_count": 1,
      "absent_count": 1,
      "leave_days": 2,
      "start_time": "08:00",
      "end_time": "17:00",
      "schedule_source": "office",
      "special_schedule": false,
      "user": {
        "id": 123,
        "name": "Jane Doe",
        "email": "user@example.com",
        "role": "HR"
      }
    }
  ]
}
```

### GET /attendance/report
Query: `month=YYYY-MM`
Response 200:
```json
{
  "summary": {
    "month": "2025-01",
    "present_days": 18,
    "late_count": 2,
    "early_checkout_count": 1,
    "absent_count": 1,
    "leave_days": 2,
    "start_time": "08:00",
    "end_time": "17:00",
    "schedule_source": "office",
    "special_schedule": false
  },
  "daily": [
    {
      "date": "2025-01-02",
      "status": "Present",
      "first_in": "2025-01-02 08:05:00",
      "last_out": "2025-01-02 17:02:00",
      "first_in_category": "OUT_OF_TOWN",
      "last_out_category": "REGULAR",
      "first_in_category_label": "Dinas Luar Kota",
      "last_out_category_label": "Kantor",
      "first_in_is_out_of_town": true,
      "last_out_is_out_of_town": false,
      "late": false,
      "early_checkout": false,
      "late_reason": null,
      "late_attachment_path": null,
      "early_checkout_reason": null,
      "early_checkout_attachment_path": null,
      "first_in_proof_path": "https://example.com/api/hrms/files/attendance/api-upload/abc123.jpg",
      "last_out_proof_path": null,
      "out_of_town": true,
      "holiday_name": null,
      "notes": [
        "Check-in recorded as Dinas Luar Kota."
      ]
    }
  ]
}
```

Notes:
- Out-of-town attendance is counted as eligible/present only when the attendance log has a photo attachment.
- `first_in_proof_path` and `last_out_proof_path` are only filled for out-of-town logs.

## Leave

### GET /leave
Response 200:
```json
{
  "data": [
    {
      "id": 3,
      "requestNo": "LV-20250101-0001",
      "leaveTypeId": 2,
      "leaveTypeName": "Annual Leave",
      "leaveTypeCode": "AL",
      "startDate": "2025-01-10",
      "endDate": "2025-01-12",
      "daysCount": 3,
      "reason": "Family event",
      "status": "Pending",
      "attachmentPath": "writable/uploads/leaves/LV-20250101-0001/file.pdf"
    }
  ]
}
```

### POST /leave
Request (JSON):
```json
{
  "leave_type_id": 2,
  "start_date": "2025-01-10",
  "end_date": "2025-01-12",
  "reason": "Family event"
}
```
Request (multipart form-data):
- `leave_type_id`: `2`
- `start_date`: `2025-01-10`
- `end_date`: `2025-01-12`
- `reason`: `Family event`
- `attachment`: (file)

Response 201:
```json
{
  "id": 3,
  "requestNo": "LV-20250101-0001",
  "status": "Pending"
}
```
Notes:
- Leave requests are created with `Pending` status. If there is no approval route configured, the request is still created and can be approved by any user with leave-approval access.

### GET /leave/{id}
Response 200:
```json
{
  "id": 3,
  "requestNo": "LV-20250101-0001",
  "requester": {
    "id": 12,
    "name": "Jane Doe",
    "email": "jane@example.com"
  },
  "leaveTypeId": 2,
  "leaveTypeName": "Annual Leave",
  "leaveTypeCode": "AL",
  "requiresAttachment": 0,
  "maxDaysPerRequest": 5,
  "startDate": "2025-01-10",
  "endDate": "2025-01-12",
  "daysCount": 3,
  "reason": "Family event",
  "status": "Pending",
  "statusRaw": "PENDING_APPROVAL",
  "currentStep": 1,
  "attachmentPath": "writable/uploads/leaves/LV-20250101-0001/file.pdf",
  "createdAt": "2025-01-01 09:15:00",
  "updatedAt": "2025-01-01 09:15:00",
  "approvals": [
    {
      "id": 4,
      "leaveRequestId": 3,
      "stepNo": 1,
      "approverId": 9,
      "approverName": "Manager Name",
      "approverEmail": "manager@example.com",
      "action": "PENDING",
      "actionAt": null,
      "notes": null
    }
  ]
}
```
Error responses:
- 403: You do not have access to this leave request.
- 404: Leave request not found.

### POST /leave/{id}/cancel
Request:
```bash
curl -X POST "/api/hrms/leave/3/cancel" \
  -H "Authorization: Bearer <accessToken>"
```
Response 200:
```json
{
  "message": "Leave request cancelled.",
  "id": 3,
  "status": "CANCELLED"
}
```
Error responses:
- 401: Unauthorized
- 403: Attendance is not required for this account.
- 404: Leave request not found.
- 409: This request cannot be cancelled. (Only `PENDING_APPROVAL` requests can be cancelled.)

### GET /leave/quota
Response 200:
```json
{
  "total": 12,
  "remaining": 9
}
```

## Error Format

All errors use JSON with a status, message, code, and optional field errors.
```json
{
  "status": "error",
  "message": "Validation failed.",
  "code": "VALIDATION_FAILED",
  "errors": {
    "start_date": "Start date and end date are required."
  }
}
```

## Performance

### GET /performance/templates/active
Query: `period_year=YYYY`
Response 200:
```json
{
  "data": {
    "id": 10,
    "name": "Performance Appraisal HRGA 2026",
    "period_year": 2026,
    "department": "HRGA",
    "is_active": 1,
    "items": [
      {
        "id": 101,
        "template_id": 10,
        "order_no": 1,
        "objective": "Efisiensi biaya operasional HRGA",
        "kpi": "Cost Saving",
        "target_value": 100,
        "unit": "%",
        "weight": 20
      }
    ]
  }
}
```

### GET /performance/submissions
Query: `period_year=YYYY` (optional)
Response 200:
```json
{
  "data": [
    {
      "id": 55,
      "template_id": 10,
      "employee_id": 123,
      "period_year": 2026,
      "total_score": 98.5,
      "status": "SUBMITTED",
      "template_name": "Performance Appraisal HRGA 2026"
    }
  ]
}
```

### POST /performance/submissions
Request:
```json
{
  "template_id": 10,
  "items": [
    { "template_item_id": 101, "actual_value": 95 }
  ]
}
```
Response 201:
```json
{
  "id": 55,
  "total_score": 98.5,
  "message": "Submission created."
}
```

### GET /performance/submissions/:id
Response 200:
```json
{
  "data": {
    "id": 55,
    "template_id": 10,
    "employee_id": 123,
    "period_year": 2026,
    "total_score": 98.5,
    "status": "SUBMITTED",
    "items": [
      {
        "id": 1,
        "template_item_id": 101,
        "actual_value": 95,
        "score_ratio": 0.95,
        "final_score": 19
      }
    ]
  }
}
```

### POST /performance/submissions/:id/cancel
Response 200:
```json
{
  "message": "Submission cancelled.",
  "id": 55,
  "status": "CANCELLED"
}
```

## Known Route Gaps (Excluded from Swagger)

These routes exist in `application/config/routes.php`, but their mapped methods are currently missing in `application/controllers/Api_hrms.php`:
- `/api/hrms/approvals/inbox`
- `/api/hrms/approvals/inbox/count`
- `/api/hrms/approvals/{id}/approve`
- `/api/hrms/approvals/{id}/reject`
- `/api/hrms/approvals/history`
