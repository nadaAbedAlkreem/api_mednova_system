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

---

## 🔴 Custom Project Financial Context (VERY IMPORTANT)

### Platform Services

The platform provides 3 core services:

1. Chat Consultation (Pusher realtime)
2. Video Consultation (Zoom scheduling)
3. Rehab Glove Device (IoT via Python → Laravel)
4. Rehab Courses

⚠️ Current scope focuses ONLY on consultation financial system.

---

### Financial Flow (Source of Truth)

- Patient pays:
  consultation_price + gateway_fee = gross_amount

- Only consultation_price is considered platform-held amount

- After payment:
  → amount goes to platform_wallet.pending_balance (ESCROW)

- Consultant receives NOTHING at this stage

---

### On Success

- consultant_earning_amount → consultant_wallet.available_balance
- platform_commission_amount → platform_wallet.available_balance

---

### On Failure / Cancel / Dispute (Patient Wins)

- consultation_price → refunded internally
- moved from:
  platform_wallet.pending_balance → patient_wallet.available_balance

- gateway_fee is NEVER refunded

---

### Wallet Semantics

- available_balance = usable money
- pending_balance = escrow (NOT owned yet)
- frozen_balance = dispute locked

---

### Patient Wallet Response (FINAL)

Patient sees ONLY:

{
"wallet": {
"total_balance": "...",
"available_balance": "...",
"pending_withdrawal": "...",
"currency": "OMR"
}
}

❌ Patient MUST NOT see:
- platform_commission
- consultant_earning
- gateway breakdown

---

### Consultant Visibility

Consultant sees ONLY:

- consultation_credit
- dispute_freeze
- dispute_release
- withdrawal

❌ Consultant MUST NOT see:
- consultation_release
- platform_fee
- payment_record
- refund

---

### Financial Tables Meaning

- consultation = source of truth
- gateway_payments = external payment
- transactions = ledger (history)
- wallets = balances only

---

### Transaction Types Meaning

- payment_record = external payment
- consultation_hold = escrow (platform pending)
- consultation_credit = consultant earning
- platform_fee = platform earning
- refund = internal refund to patient
- dispute_freeze = freeze funds
- dispute_release = resolve dispute
- withdrawal = user withdrawal

---

### Critical Rules

- NEVER modify financial values after payment success
- ALWAYS use DB::transaction
- ALWAYS use lockForUpdate for wallet updates
- NEVER duplicate webhook processing
- consultation financial values are IMMUTABLE snapshot


---

## API Response Filtering by Role (CRITICAL)

Financial data MUST be filtered at Resource layer based on user role.
NEVER send sensitive data to frontend expecting it to hide them.

### Patient Role
Patient sees in wallet:
- total_balance (computed)
- available_balance
- pending_withdrawal (alias for pending_balance)
- currency

Patient sees in payments:
- amount_paid (gross)
- gateway_fee
- consultation_price
- is_refunded + refunded_amount

Patient NEVER sees:
- platform_commission_rate / amount
- consultant_earning_amount
- frozen_balance
- payload / gateway_transaction_id
- response_code / response_message

### Consultant Role
Consultant sees in wallet:
- total_balance (computed)
- available_balance
- pending_balance (future earnings from active consultations)
- frozen_balance
- withdrawable_balance (= available_balance)

Consultant sees in consultation financials:
- consultation_price
- platform_commission_rate / amount
- your_earning (= consultant_earning_amount)

Consultant NEVER sees:
- gross_amount
- gateway_fee / gateway_commission_*
- refund details

### Allowed Transaction Types Per Role

Patient: refund, withdrawal, dispute_release
Consultant: consultation_credit, dispute_freeze, dispute_release, withdrawal

Never visible to Patient: consultation_hold, consultation_release, platform_fee, payment_record
Never visible to Consultant: consultation_release, platform_fee, payment_record, refund, consultation_hold

---

## Localization

All Resources must support Arabic (default) and English via Accept-Language header.
Labels live as const arrays inside each Resource (not in lang files for now).
If header starts with 'en' → English, otherwise → Arabic.

---

## Pagination Defaults

- Default per_page: 15
- Max per_page: 50
- Always orderByDesc('created_at')
- Always whereNull('deleted_at') on soft-deleted tables
