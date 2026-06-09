# Medical Consultation Platform — Developer Skill
# منصة الاستشارات الطبية — مرجع المطور

## CRITICAL: Read this entire file before writing any code.
## This replaces the need to discover project patterns from scratch.

---

## 1. Project Identity

- **Framework:** Laravel (PHP)
- **Project path:** D:\nada\projects\appointment-booking-system
- **Database:** MySQL, DB name: appointment_booking_system
- **Currency:** OMR (3 decimal places always — number_format($x, 3, '.', ''))
- **Language:** Arabic first, English supported via Accept-Language header
- **Environment:** APP_ENV=local (real database, not test)

---

## 2. Architecture — Layered (STRICT)

```
FormRequest → Controller → Service → Repository → Model
```

- **Controllers:** thin, use ResponseTrait (successResponse / errorResponse)
- **Services:** all business logic, DI via constructor (protected typed properties)
- **Repositories:** data access only, injected as interfaces
- **FormRequests:** ALL validation here — never in Service or Controller
- **Resources:** serialization only — never query DB, never call services

---

## 3. FormRequest Pattern (MANDATORY — copy exactly)

```php
namespace App\Http\Requests\api\consultation;

class ExampleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array {
        return [
            'field' => ['required', 'string', 'max:100'],
        ];
    }

    // CRITICAL: copy this verbatim — project-wide error format
    protected function failedValidation(Validator $validator) {
        $errors = $validator->errors()->messages();
        $formattedErrors = [];
        foreach ($errors as $field => $messages) {
            $formattedErrors[$field] = $messages[0];
        }
        throw new ValidationException($validator, response()->json([
            'success' => false,
            'message' => __('messages.ERROR_OCCURRED'),
            'data' => $formattedErrors,
            'status' => 'Internal Server Error'
        ], 422));
    }

    public function messages(): array {
        return [
            'field.required' => __('validation.required', ['attribute' => __('validation.attributes.field')]),
        ];
    }
}
```

---

## 4. Controller Pattern

```php
use App\Traits\ResponseTrait;

class ExampleController extends Controller
{
    use ResponseTrait;

    public function __construct(
        protected ExampleService $exampleService,
    ) {}

    public function store(StoreExampleRequest $request): JsonResponse
    {
        try {
            $result = $this->exampleService->create($request->user('api'), $request->validated());
            return $this->successResponse(__('messages.CREATE_SUCCESS'), new ExampleResource($result), 201);
        } catch (DomainException $e) {
            return $this->errorResponse($e->getMessage(), [], 422);
        } catch (\Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }
}
```

---

## 5. Service Pattern

```php
class ExampleService
{
    public function __construct(
        protected IWalletRepositories $wallets,
        protected IExampleRepositories $examples,
        protected FinancialTransactionService $financialTransactionService,
    ) {}

    public function doFinancialWork($user, array $data): void
    {
        DB::transaction(function () use ($user, $data) {
            // financial work here
            // wallet updates with lockForUpdate

            DB::afterCommit(function () use ($user) {
                // notifications AFTER successful commit only
                event(new ConsultationRequested($consultation, $message, $eventType));
            });
        });
    }
}
```

---

## 6. Repository Auto-Binding Rule

`RepositoryServiceProvider` auto-binds:
```
I{ModelName}Repositories → {ModelName}Repository
```

**CRITICAL:** Repository interface name MUST match Model name exactly.
- Model: `WithdrawalRequest` → Interface: `IWithdrawalRequestRepositories`
- Model: `BankAccount` → Interface: `IBankAccountRepositories`
- Exception: manually bound in RepositoryServiceProvider if names differ

---

## 7. Financial System — Core Rules

### Money Flow
```
Patient pays
  → consultation_price → platform_wallet.pending_balance (escrow)
  → gateway_fee → paid to gateway directly (NEVER refunded)

Consultation completes → review_window (48h)
  → No dispute within 48h → Settlement:
      platform.pending -= consultation_price
      consultant.available += consultant_earning_amount
      platform.available += platform_commission_amount

Dispute opened by patient:
  → platform.pending -= consultation_price
  → platform.frozen += consultation_price

Dispute resolved for patient:
  → platform.frozen -= consultation_price
  → patient.available += consultation_price

Dispute resolved for consultant:
  → platform.frozen -= consultation_price
  → consultant.available += consultant_earning_amount
  → platform.available += platform_commission_amount
```

### THE GOLDEN RULE (NEVER VIOLATE)
```
consultation_price = consultant_earning_amount + platform_commission_amount
```
Verify with bcadd before any settlement. If mismatch → abort + log critical.

### Amount Rules
- Always deduct `consultation_price` (full amount) from pending — NEVER just earning
- Always use `bcadd()` / `bccomp()` for financial comparisons — NEVER `==` or `===`
- Format all money: `number_format($value, 3, '.', '')`

---

## 8. Wallet Rules

### Platform Wallet
```php
// owner_type = 'platform', owner_id = 1
$platformWallet = $this->wallets->getPlatformWallet(); // with lockForUpdate
$platformWallet = $this->wallets->getPlatformWalletReadOnlyInRepo(); // no lock
```

### User Wallet
```php
$wallet = $this->wallets->getOrCreateByOwnerForUpdate($user->id, 'OMR');
```

### Lock Order (DEADLOCK PREVENTION)
```
ALWAYS lock platform wallet FIRST, then user wallet.
NEVER reverse this order.
```

### Balance Updates (use atomic SQL — never fetch-compute-save)
```php
$wallet->decrement('pending_balance', $amount);
$wallet->increment('available_balance', $amount);
```

---

## 9. Transaction Creation

```php
$this->financialTransactionService->createWalletEntry(
    reference: $consultation,           // Model
    gatewayPaymentId: null,             // int|null
    transactionType: TransactionType::CONSULTATION_CREDIT->value,
    entryType: EntryType::ENTRY_CREDIT->value,
    walletId: $consultantWallet->id,
    grossAmount: (float) $consultation->consultation_price,
    netAmount: (float) $consultation->consultant_earning_amount,
    currency: 'OMR',
    status: AmountStatus::STATUS_AVAILABLE->value,
    meta: ['operation' => 'settlement', 'role' => 'consultant'],
    platformCommission: (float) $consultation->platform_commission_amount,
    vatAmount: 0,
);
```

### Transaction Types (TransactionType enum)
```
consultation_hold    → escrow when payment received
consultation_release → release from escrow during settlement
consultation_credit  → consultant earnings
platform_fee         → platform commission
refund               → refund to patient
dispute_freeze       → freeze for dispute (debit from platform pending)
dispute_release      → unfreeze after dispute resolved
withdrawal           → user requests withdrawal
withdrawal_reversal  → withdrawal rejected, money returns
payment_record       → audit record of gateway payment
```

---

## 10. Financial Status Flow (FinancialStatus enum)

```
unpaid → held → review_window → withdrawable → withdrawn
                             → frozen → refunded_internal (patient wins)
                                      → withdrawable (consultant wins)
         → refunded_internal (cancelled before service)
```

### What Each Role Sees (financial_status_label)
```
Status              Patient Label              Consultant Label
unpaid              غير مدفوعة                 بانتظار الدفع
held                تم الدفع                   بانتظار تقديم الخدمة
review_window       فترة المراجعة              بانتظار التسوية
withdrawable        مكتملة                     قابل للسحب
withdrawn           مكتملة                     تم السحب
refunded            تم الاسترداد               تم إلغاء الاستشارة
refunded_internal   تم الاسترداد               تم إلغاء الاستشارة
frozen              قيد المراجعة               قيد المراجعة
payment_suspended   معلّقة مؤقتاً              معلّقة مؤقتاً
```

---

## 11. Service Signatures (Financial)

```php
// Settlement
SettlementService::settle(ConsultationChatRequest|ConsultationVideoRequest $consultation): void
  // Pre-conditions: financial_status=review_window, review_deadline expired
  // Validates accounting identity before settling

// Dispute Opening
DisputeService::execute($consultation, Customer $patient, ?string $reason = null): void
  // Pre-conditions: financial_status=review_window, now() <= review_deadline
  // Patient must own the consultation

// Dispute Resolution
DisputeResolutionService::resolveForPatient(Dispute $dispute, $admin, ?string $note): void
DisputeResolutionService::resolveForConsultant(Dispute $dispute, $admin, ?string $note): void

// Refund
ConsultationRefundService::processInternalRefund($consultation, string $reason = 'manual_refund'): void
  // MUST be called inside DB::transaction() + lockForUpdate on consultation
  // Accepts financial_status: 'held' OR 'frozen'

// Withdrawal (User)
WithdrawalService::requestWithdrawal(Customer $user, float $amount): WithdrawalRequest
WithdrawalService::cancelWithdrawal(Customer $user, int $withdrawalId): void
WithdrawalService::getUserWithdrawals(Customer $user, int $perPage): LengthAwarePaginator

// Withdrawal (Admin)
AdminWithdrawalService::process(WithdrawalRequest $withdrawal, $admin, array $data): void
  // $data['action'] = 'approve' | 'reject'
  // approve: transfer_reference required, transfer_proof optional
  // reject: admin_note required

// Bank Account
BankAccountService::store(Customer $user, array $data): BankAccount
BankAccountService::verifyOtp(Customer $user, string $otp): BankAccount
BankAccountService::getDefault(Customer $user): ?BankAccount
```

---

## 12. Notification Pattern

```php
// Standard flow: event → listener → job → broadcast
// ConsultationRequested requires a consultation model

event(new \App\Events\ConsultationRequested(
    $consultation,
    __('messages.event_message', ['param' => $value]),
    'event_type_string'  // determines recipient channel
));

// Event types → channels (in ConsultationRequestedBroadcast):
// consultant channel: dispute_opened_consultant, settlement_completed_consultant,
//                     active_by_patient, requested, cancelled_by_patient
// patient channel:    dispute_opened_patient, review_window_opened,
//                     settlement_completed_patient, accepted,
//                     cancelled_by_consultant, review_window_expiring_patient
// admin channel:      dispute_opened_admin → private-admin.notifications
// both channels:      cancelled_by_system, active, completed, reminder_for_all
```

---

## 13. Role Detection

```php
// Customer types
'patient'                  → patient
'therapist'                → consultant
'rehabilitation_center'    → consultant

// isConsultant check
private function isConsultant($user): bool
{
    return in_array($user->type_account, [
        ConsultantType::THERAPIST->value,           // 'therapist'
        ConsultantType::REHABILITATION_CENTER->value, // 'rehabilitation_center'
    ], true);
}
```

---

## 14. Resource Pattern

```php
// Language detection (standard across all Resources)
private function resolveLocale(Request $request): string
{
    return str_starts_with($request->header('Accept-Language', ''), 'en') ? 'en' : 'ar';
}

// Enum → string (handle both cases)
$status = $this->financial_status instanceof \BackedEnum
    ? $this->financial_status->value
    : (string) $this->financial_status;
```

---

## 15. Security Rules

### Encrypted Fields (BankAccount model)
```php
protected $casts = [
    'swift_code'          => 'encrypted',
    'account_holder_name' => 'encrypted',
];

'account_number' 'iban' were encrypted using a library called ciphersweet
// Hashes stored alongside for uniqueness:
// account_number_hash = hash('sha256', $rawAccountNumber)
// iban_hash = hash('sha256', $rawIban)
```

### Role-Based Data Visibility
```
Patient NEVER sees: platform_commission, consultant_earning, gateway_transaction_id, payload
Consultant NEVER sees: gross_amount, gateway_fee, patient refund details
Admin sees: everything (unmasked bank data for withdrawal processing)
```

---

## 16. Admin Infrastructure

```
Route prefix: /api/admin or /api/control-panel (check routes/api.php)
Middleware: auth:admin

Admin endpoints:
GET  /admin/financial/dashboard
GET  /admin/financial/revenue
GET  /admin/financial/escrow
GET  /admin/financial/transactions
GET  /admin/disputes
GET  /admin/disputes/{id}
POST /admin/disputes/{id}/resolve   → { resolution: 'refund'|'release', admin_note }
GET  /admin/withdrawals
GET  /admin/withdrawals/{id}
POST /admin/withdrawals/{id}/process → { action: 'approve'|'reject', ... }
GET  /admin/withdrawals/{id}/proof
```

---

## 17. Withdrawal Config

```php
// config/financial.php
'withdrawal' => [
    'min_amount' => 5.000,
    'max_amount' => 5000.000,
    'currency'   => 'OMR',
]
```

### WithdrawalStatus enum values
```
pending_review    → awaiting admin action
processing        → being processed
transferred       → approved and transferred
rejected          → rejected, money returned to available
cancelled_by_user → user cancelled before processing
```

---

## 18. Audit Command Reference

```bash
php artisan financial:audit
```

All 10 checks are implemented.

Each check method returns:
```php
['name' => 'Check N — Name', 'status' => 'passed|warning|critical', 'details' => []]
```

---

## 19. Common Mistakes to Avoid

```
❌ Using == for decimal comparison   → use bccomp()
❌ Decrementing consultant_earning from pending   → always decrement consultation_price
❌ Firing events before DB commit   → always use DB::afterCommit()
❌ Missing lockForUpdate on wallets   → always lock before read-then-write
❌ Creating repository without matching model name   → breaks auto-binding
❌ Arabic strings in PHP terminal   → paste from message editor, never retype
❌ financial_status as string when model has enum cast   → use ->value or BackedEnum check
❌ account_number_hash missing on BankAccount create   → must compute and store hash
```

---

## 20. Testing Notes

```
E2E Seeder: database/seeders/FinancialE2ETestSeeder.php
  - Use real services, not raw DB inserts
  - bccomp() for all decimal assertions
  - Individual try/catch per scenario
  - prefix: 'E2E_TEST_' on test users
  - Do NOT delete test data after

Audit Command: php artisan financial:audit
  - READ ONLY, never modifies data
  - Returns exit code 1 if any CRITICAL found
  - Logs to Log::channel('financial')
```
