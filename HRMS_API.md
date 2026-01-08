# HRMS API (Mobile) Documentation

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
    "role": "HR"
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
  "role": "HR"
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
  "role": "HR"
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
  }
}
```
Notes:
- `allowed_ssids`, `attendance_response_times`, `attendance_history_days`, and `attendance_recap_months` are read from the active row in `offices`.
 - If both `allowed_bssids` and `allowed_ssids` are set, the WiFi proof must match both.

## Attendance

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

### POST /attendance/check-in
Request:
```json
{
  "lat": -6.200123,
  "lng": 106.816789,
  "gpsAccuracy": 15,
  "distanceMeters": 120,
  "wifiProof": {
    "bssid": "aa:bb:cc:dd:ee:ff"
  }
}
```
Response 200:
```json
{
  "ok": true,
  "type": "IN",
  "distanceMeters": 120.12,
  "office": {
    "id": 1,
    "name": "HQ Office"
  }
}
```

### POST /attendance/check-out
Request:
```json
{
  "lat": -6.200123,
  "lng": 106.816789,
  "gpsAccuracy": 15,
  "distanceMeters": 120,
  "wifiProof": {
    "bssid": "aa:bb:cc:dd:ee:ff"
  }
}
```
Response 200:
```json
{
  "ok": true,
  "type": "OUT",
  "distanceMeters": 120.12,
  "office": {
    "id": 1,
    "name": "HQ Office"
  }
}
```

### GET /attendance/history
Response 200:
```json
{
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
  "end_time": "17:00"
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
    "end_time": "17:00"
  },
  "daily": [
    {
      "date": "2025-01-02",
      "status": "Present",
      "first_in": "2025-01-02 08:05:00",
      "last_out": "2025-01-02 17:02:00",
      "late": false,
      "early_checkout": false,
      "holiday_name": null
    }
  ]
}
```

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

### GET /leave/quota
Response 200:
```json
{
  "total": 12,
  "remaining": 9
}
```

## Error Format

All errors use JSON with a message and optional field errors.
```json
{
  "message": "Validation failed.",
  "errors": {
    "start_date": "Start date and end date are required."
  }
}
```
