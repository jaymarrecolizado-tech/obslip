# OBPASS_MASTER_SPEC_v1.md

## Project
DICT Region II Official Business Pass Slip System

Production URL:
https://obpass.dictr2.cloud

## Objective
Digitize the Official Business Pass Slip process while supporting:
- Digital workflow
- Printed workflow
- Hybrid workflow

Platforms:
- Web Browser
- Android
- iPhone
- Tablet Guard Kiosk

## Technology Stack

Backend:
- Laravel 11
- PHP 8.3
- MySQL 8
- Redis
- Horizon
- Sanctum
- Spatie Permission
- Browsershot
- Intervention Image (image processing)
- SimpleSoftwareIO/QrCode (QR generation)

Admin:
- Filament 3

Frontend:
- React Native Expo
- Expo Router
- Zustand
- TanStack Query
- SQLite (offline)

## Roles

Admin
HR
Supervisor
Guard
Employee

## Permission Matrix

| Action | Admin | HR | Supervisor | Guard | Employee |
|--------|-------|-----|------------|-------|----------|
| Create pass slip | Yes | Yes | Yes | No | Yes |
| Edit own draft | Yes | Yes | Yes | No | Yes (own) |
| Cancel own slip | Yes | Yes | Yes | No | Yes (own, draft/submitted only) |
| Approve slip | No | No | Yes (own department) | No | No |
| Return slip with reason | No | No | Yes (own department) | No | No |
| View all slips | Yes | Yes | No | Yes (today only) | No |
| View own slips | Yes | Yes | Yes | Yes | Yes |
| Log departure | No | No | No | Yes | No |
| Log arrival | No | No | No | Yes | No |
| Scan QR | No | No | No | Yes | No |
| Manage users | Yes | No | No | No | No |
| Manage employees | Yes | Yes | No | No | No |
| Manage vehicles | Yes | Yes | No | No | No |
| Manage departments | Yes | No | No | No | No |
| View audit logs | Yes | Yes | No | No | No |
| View reports | Yes | Yes | Yes | No | No |
| Submit certificate | Yes | Yes | Yes | No | Yes |
| Verify certificate | Yes | Yes | No | No | No |
| Manage settings | Yes | No | No | No | No |
| Push notifications | Yes | Yes | No | No | No |

## Workflow

Draft → Submitted → Approved → Departed → Arrived → Certificate Submitted → Verified → Completed

Alternate paths:
- Submitted → Returned → Submitted (resubmit)
- Draft → Cancelled
- Submitted → Cancelled
- Approved → Cancelled (admin/supervisor only)

State rules:
- Employee can cancel only Draft or Submitted slips
- Supervisor can only approve/return slips from their own department
- Guard can only log departure/arrival for slips with status Approved/Departed
- All transitions are audit logged
- Cancelled slips cannot be reactivated

## Core Tables

All tables use UUID primary keys and soft deletes unless noted.

### users
- id (uuid, pk)
- name (string)
- email (string, unique)
- phone (string, nullable)
- password (string)
- avatar_path (string, nullable)
- is_active (boolean, default: true)
- department_id (uuid, fk, nullable)
- position (string, nullable)
- last_login_at (timestamp, nullable)
- remember_token (string)
- created_at (timestamp)
- updated_at (timestamp)
- deleted_at (timestamp, nullable)

### departments
- id (uuid, pk)
- name (string)
- code (string, unique)
- head_id (uuid, fk → users, nullable)
- is_active (boolean, default: true)
- created_at (timestamp)
- updated_at (timestamp)
- deleted_at (timestamp, nullable)

### employees
- id (uuid, pk)
- employee_number (string, unique)
- first_name (string)
- last_name (string)
- middle_name (string, nullable)
- suffix (string, nullable)
- email (string, nullable)
- phone (string, nullable)
- department_id (uuid, fk)
- position (string)
- date_hired (date, nullable)
- employment_status (enum: regular, contractual, coterminous, job_order)
- is_active (boolean, default: true)
- created_at (timestamp)
- updated_at (timestamp)
- deleted_at (timestamp, nullable)

### vehicles
- id (uuid, pk)
- plate_number (string, unique)
- make (string)
- model (string)
- year (integer, nullable)
- color (string, nullable)
- owner_id (uuid, fk → employees, nullable)
- is_active (boolean, default: true)
- created_at (timestamp)
- updated_at (timestamp)
- deleted_at (timestamp, nullable)

### pass_slips
- id (uuid, pk)
- slip_number (string, unique) — auto-generated: OB-{YYYY}-{sequence}
- date (date)
- purpose (text)
- transport_type (enum: company_vehicle, personal_vehicle, public_transport, on_foot)
- status (enum: draft, submitted, returned, approved, departed, arrived, certificate_submitted, verified, completed, cancelled)
- creator_id (uuid, fk → users)
- supervisor_id (uuid, fk → users, nullable)
- approver_id (uuid, fk → users, nullable)
- employee_id (uuid, fk → employees)
- department_id (uuid, fk → departments)
- vehicle_id (uuid, fk → vehicles, nullable)
- departure_time (timestamp, nullable)
- arrival_time (timestamp, nullable)
- approved_at (timestamp, nullable)
- completed_at (timestamp, nullable)
- cancelled_at (timestamp, nullable)
- returned_reason (text, nullable)
- duration_hours (decimal, nullable)
- is_emergency (boolean, default: false)
- pdf_path (string, nullable)
- qr_code (string, nullable) — UUID for public verification
- created_at (timestamp)
- updated_at (timestamp)
- deleted_at (timestamp, nullable)

### pass_slip_employee (pivot)
- id (uuid, pk)
- pass_slip_id (uuid, fk)
- employee_id (uuid, fk)
- created_at (timestamp)
- updated_at (timestamp)

### certificates
- id (uuid, pk)
- pass_slip_id (uuid, fk)
- type (enum: physical, digital, hybrid)
- office_name (string)
- representative_name (string)
- representative_position (string)
- representative_contact (string, nullable)
- time_from (time)
- time_to (time)
- signature_path (string, nullable)
- attachment_path (string, nullable)
- status (enum: draft, submitted, verified)
- submitted_by (uuid, fk → users)
- verified_by (uuid, fk → users, nullable)
- verified_at (timestamp, nullable)
- created_at (timestamp)
- updated_at (timestamp)
- deleted_at (timestamp, nullable)

### audit_logs
- id (uuid, pk)
- user_id (uuid, fk → users, nullable)
- auditable_type (string)
- auditable_id (uuid)
- action (string) — created, updated, deleted, state_transition
- old_values (json, nullable)
- new_values (json, nullable)
- ip_address (string, nullable)
- user_agent (string, nullable)
- created_at (timestamp)
- updated_at (timestamp)

### notifications
- id (uuid, pk)
- type (string)
- notifiable_type (string)
- notifiable_id (uuid)
- data (json)
- read_at (timestamp, nullable)
- created_at (timestamp)
- updated_at (timestamp)

### device_tokens
- id (uuid, pk)
- user_id (uuid, fk)
- token (string)
- platform (enum: android, ios)
- last_used_at (timestamp, nullable)
- created_at (timestamp)
- updated_at (timestamp)

### settings
- id (uuid, pk)
- key (string, unique)
- value (text, nullable)
- group (string) — general, notification, pdf
- type (string) — string, integer, boolean, json
- created_at (timestamp)
- updated_at (timestamp)

## Pass Slip Fields

- slip_number (auto-generated)
- date
- purpose
- transport_type
- status
- creator_id
- supervisor_id
- approver_id
- employee_id
- department_id
- vehicle_id
- departure_time
- arrival_time
- approved_at
- completed_at
- returned_reason
- duration_hours
- is_emergency
- pdf_path
- qr_code

## Certificate

Supports:
- Physical
- Digital
- Hybrid

Fields:
- pass_slip_id
- type
- office_name
- representative_name
- representative_position
- representative_contact
- time_from
- time_to
- signature_path
- attachment_path
- status
- submitted_by
- verified_by

## QR Verification

Public URL:

/verify/{qr_code}

Must display:
- Slip Number
- Employee Name
- Status
- Date
- Purpose
- Duration

No sensitive data. Read-only. No authentication required.

## APIs

Base URL: /api/v1

Auth:
- POST /api/v1/login
- POST /api/v1/logout
- GET /api/v1/me
- POST /api/v1/forgot-password
- POST /api/v1/reset-password

Pass Slips:
- GET /api/v1/pass-slips
- GET /api/v1/pass-slips/{id}
- POST /api/v1/pass-slips
- PUT /api/v1/pass-slips/{id}
- DELETE /api/v1/pass-slips/{id}
- POST /api/v1/pass-slips/{id}/submit
- POST /api/v1/pass-slips/{id}/approve
- POST /api/v1/pass-slips/{id}/return
- POST /api/v1/pass-slips/{id}/cancel
- GET /api/v1/pass-slips/{id}/pdf

Employees:
- GET /api/v1/employees
- GET /api/v1/employees/{id}
- POST /api/v1/employees
- PUT /api/v1/employees/{id}
- DELETE /api/v1/employees/{id}

Vehicles:
- GET /api/v1/vehicles
- GET /api/v1/vehicles/{id}
- POST /api/v1/vehicles
- PUT /api/v1/vehicles/{id}
- DELETE /api/v1/vehicles/{id}

Departments:
- GET /api/v1/departments
- GET /api/v1/departments/{id}
- POST /api/v1/departments
- PUT /api/v1/departments/{id}
- DELETE /api/v1/departments/{id}

Certificates:
- GET /api/v1/certificates
- GET /api/v1/certificates/{id}
- POST /api/v1/certificates
- PUT /api/v1/certificates/{id}
- POST /api/v1/certificates/{id}/verify

Notifications:
- GET /api/v1/notifications
- PUT /api/v1/notifications/{id}/read
- PUT /api/v1/notifications/read-all
- POST /api/v1/device-tokens

Guard Actions:
- GET /api/v1/guard/search-slip?slip_number=
- POST /api/v1/guard/scan-qr
- POST /api/v1/guard/log-departure/{pass_slip_id}
- POST /api/v1/guard/log-arrival/{pass_slip_id}

Use Sanctum token-based authentication.

Pagination: 15 per default, max 100.
All responses use JSON envelope: { success, data, message, errors }

## Filament Resources

EmployeeResource
VehicleResource
PassSlipResource
CertificateResource
UserResource
DepartmentResource
AuditLogResource
SettingResource

## Filament Pages

Dashboard — summary cards, charts, recent slips
Reports — filterable by date range, department, status
GuardKiosk — simplified interface for guard tablet

## Mobile Features

- Login (email/password)
- Biometric Login (Face ID / Fingerprint)
- Dashboard (my stats, pending approvals)
- My Slips (list with status filters)
- Create Slip (form with employee/vehicle selection)
- Edit Draft Slip
- Cancel Slip
- Notifications (list, mark read)
- QR Scanner (scan to verify)
- Certificate Upload (photo/document)
- PDF Viewer (view/download slip PDF)
- Offline Sync (queue writes, sync when online)

## Guard Kiosk

Functions:
- Search Slip (by number or employee name)
- Scan QR (camera-based)
- Log Departure (with timestamp confirmation)
- Log Arrival (with timestamp confirmation)
- Today's Activity Log

Kiosk mode: full-screen, no browser chrome, minimal navigation.

## PDF

Match official DICT form exactly.
Generate duplicate copies (original + duplicate).
Include QR code in bottom-right corner.
Fields: slip number, date, employee name, department, purpose, supervisor, vehicle, time.
Use Browsershot with headless Chrome.

## Notifications

Database Notifications (in-app)
FCM Push Notifications (mobile)

Events:
- SlipSubmitted → Supervisor + Employee
- SlipApproved → Employee + Guard
- SlipReturned → Employee (with reason)
- SlipDeparted → Supervisor
- SlipArrived → Supervisor
- CertificateSubmitted → HR + Admin
- CertificateVerified → Employee
- SlipCompleted → Employee

## Deployment

Hostinger KVM 2

Ubuntu 24.04
Nginx
PHP-FPM 8.3
MySQL 8
Redis
Supervisor (queue workers + Horizon)
SSL (Let's Encrypt)

CI/CD:
- GitHub Actions
- Deploy via SSH on main branch push
- Run migrations automatically
- Cache clearing

Backup:
- Daily MySQL dump via cron
- Upload to cloud storage
- 30-day retention

## Coding Standards

- SOLID
- Service Layer (app/Services/)
- Repository Pattern (app/Repositories/)
- Form Requests (app/Http/Requests/)
- Policies (app/Policies/)
- API Resources (app/Http/Resources/)
- Feature Tests (tests/Feature/)
- UUID primary keys
- Soft Deletes
- Strict typing (declare(strict_types=1))
- PSR-12 coding style

## Project Structure

```
obpass/
├── app/
│   ├── Enums/
│   ├── Http/
│   │   ├── Controllers/Api/V1/
│   │   ├── Filament/
│   │   ├── Middleware/
│   │   ├── Requests/
│   │   └── Resources/
│   ├── Models/
│   ├── Notifications/
│   ├── Policies/
│   ├── Repositories/
│   ├── Services/
│   └── Observers/
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/
│   └── views/
│       └── pdf/
├── routes/
│   ├── api.php
│   ├── web.php
│   └── channels.php
├── storage/
├── tests/
│   ├── Feature/
│   └── Unit/
└── mobile/ (React Native Expo)
```

## AI Generation Instructions

Generate in phases:

1. Architecture Review (DONE)
2. Migrations
3. Models & Relationships
4. Roles, Permissions, Policies
5. Form Requests & API Resources
6. Services & Repositories
7. API Controllers
8. Filament Resources
9. PDF Generation
10. QR Verification
11. Notifications
12. Mobile App
13. Tests
14. Deployment

Never skip dependencies.
