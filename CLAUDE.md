# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Install dependencies and bootstrap (auto-generates .env, creates SQLite DB, runs migrations)
composer install

# Run development servers (Laravel + queue + logs + Vite, concurrently)
composer dev

# Run all tests (clears config cache first)
composer test

# Run a single test file or method
php artisan test tests/Feature/SomeTest.php
php artisan test --filter=test_method_name

# Frontend assets
npm run dev      # Vite dev server
npm run build    # Production build

# Laravel maintenance
php artisan migrate
php artisan migrate:fresh --seed
php artisan queue:work
```

## Architecture Overview

**Healthcare appointment booking platform** for video/chat consultations between patients and service providers (therapists, rehabilitation centers). Includes payment processing, wallet management, smart glove IoT device integration, and program/course delivery.

### User Types (`type_account`)
- `patient` — books consultations
- `therapist` — individual provider
- `rehabilitation_center` — organization provider
- `admin` — separate guard (`auth:admin`) for control panel

### Authentication
Laravel Sanctum with two guards: `api` (customers) and `admin` (admins). Custom middleware `EnsureAccountType` validates `type_account` + `account_status=active` + `approval_status=approved`.

### Layered Architecture

**Controllers → Services → Repositories → Models**

- `app/Http/Controllers/Api/` — thin controllers; delegate to services via DI
- `app/Services/Api/` — all business logic, organized by domain: `Consultation/`, `Financial/`, `Customer/`, `Payment/`
- `app/Repositories/Eloquent/` — data access implementing `BaseRepositoryInterface` (73-method interface for CRUD, pagination, soft deletes, relationship queries)
- `app/Http/Requests/` — all input validation (97 Form Request classes)
- `app/Http/Resources/` — all API serialization (29 Resource classes)
- `app/Policies/` — object-level authorization (25+ policy classes); controllers call `$this->authorize()`

All controllers use `ResponseTrait` for uniform `{ data, message, status }` API responses.

### Core Domains

**Consultation Booking**
- Models: `ConsultationVideoRequest`, `ConsultationChatRequest`, `AppointmentRequest`
- Status flow: `pending → accepted → completed/cancelled`
- Key services: `ConsultationStatusService` (DB transactions), `ConsultantAvailabilityService`, `ZoomMeetingService`
- Reschedule requests have their own approval workflow

**Financial System**
- `Wallet` (polymorphic, owned by `Customer`), `Transaction`, `GatewayPayment`
- Payment gateway: **Amwal** (Omani Rial / OMR) — see `app/Services/Api/Payment/AmwalPayService.php` and `config/amwal.php`
- Financial fields on consultations: `consultation_price`, `gateway_commission_rate`, `gateway_commission_amount`, `net_amount`
- `financial_status` is tracked separately from `status` on consultations
- Key services: `ConsultationRefundService`, `PaymentFeeCalculator`, `ConsultantFinancialService`
- Custom exceptions: `ConsultantWalletNotFoundException`, `InsufficientWalletBalanceException`, `GatewayException`
- DTOs in `app/DTOs/Financial/` (e.g., `ManualRefundMeta`)

**Smart Glove IoT**
- Models: `GloveDevice`, `GloveCommand`, `GloveData`, `GloveError`, `GloveSession`
- Device token validated via `VerifyDeviceToken` middleware
- Real-time data broadcast via Pusher (`GloveDataUpdated` event)

**Programs/Courses**
- `Program` (polymorphic creator: therapist or center), `ProgramVideo`, `ProgramEnrollment`, `CustomerCourseProgress`
- Admin approval workflow before publishing

### Key Events
`ConsultationRequested`, `ConsultationVideoApproval`, `MessageSent`, `GloveDataUpdated`, `CustomerApprovalStatusChanged`, `TemporaryPackageAssigned`

### Database
- **Dev/test:** SQLite (`:memory:` for tests per `phpunit.xml`)
- Session, cache, queue: all use `database` driver
- Soft deletes on most models
- Polymorphic patterns: `Wallet` owner, `Rating` reviewee, `Program` creator, `Notification` notifiable
- Migration naming: `YYYY_MM_DD_HHMMSS_description.php`

### External Integrations
- **Zoom** — video consultation meetings (`ZoomMeetingService`)
- **Pusher** — real-time broadcasting
- **Sentry** — exception monitoring
- **Laravel Socialite** — OAuth social login
- **Maatwebsite Excel** — data export
- **Dedoc Scramble** — auto API documentation
- **Omnix** — external service (`config/omnix.php`)
