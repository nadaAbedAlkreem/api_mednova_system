<?php
namespace App\Services\Api\Program;

use App\Enums\AccountStatus;
use App\Enums\ProgramStatus;
use App\Enums\StatusType;
use App\Events\CustomerApprovalStatusChanged;
use App\Mail\AccountApprovedMail;
use App\Mail\AccountRejectedMail;
use App\Models\Customer;
use App\Models\Program;
use App\Models\ProgramVideos;
use App\Repositories\IAccountReviewRepositories;
use App\Repositories\ICustomerRepositories;
use App\Repositories\IProgramRepositories;
use App\Services\Api\Customer\UploadService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ProgramService
{

    protected Program $model;
    protected IProgramRepositories $programRepositories;

    public function __construct(IProgramRepositories $programRepositories ,Program $program)
    {
        $this->programRepositories = $programRepositories;
        $this->model = $program;

    }

    public function getAll(array $filters = [], int $limit = 10)
    {
        $query = $this->model->query()->with('creator');
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['is_approved'])) {
            $query->where('is_approved', $filters['is_approved']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('title_ar', 'LIKE', "%{$search}%")
                    ->orWhere('description_ar', 'LIKE', "%{$search}%");
            });
        }
        return $query->paginate($limit);
    }


//    public function updateApprovalStatus(Customer $customer,StatusType $status , string $reason = ''): Customer
//    {
//         $currentAdmin = Auth('admin')->user();
//        return DB::transaction(function () use ($customer, $status, $reason, $currentAdmin) {
//             if ($customer->approval_status == $status->value) {
//                return $customer;
//            }
//
//            if (($customer->approval_status ==StatusType::APPROVED->value) && ($status == StatusType::REJECTED)) {
//                throw ValidationException::withMessages([
//                    'approval_status' => __('messages.CANNOT_REJECT_APPROVED_ACCOUNT')
//                ]);
//            }
//
//            if ($status === StatusType::APPROVED) {
//                 if ($customer->is_banned) {
//                    throw ValidationException::withMessages([
//                        'approval_status' => __('messages.CANNOT_APPROVE_BLOCKED')
//                    ]);
//                }
//
//                if (!$customer->email_verified_at) {
//                    throw ValidationException::withMessages([
//                        'approval_status' => __('messages.EMAIL_NOT_VERIFIED')
//                    ]);
//                }
//
//                if (!$customer->isProfileCompleted()) {
//                    throw ValidationException::withMessages([
//                        'approval_status' => __('messages.PROFILE_NOT_COMPLETED')
//                    ]);
//                }
//            }
//
//            $customer->approval_status = $status;
//            $customer->save();
//
//            event(new CustomerApprovalStatusChanged(
//                $customer,
//                $status,
//                $reason,
//                $currentAdmin->id
//            ));
//
//            return $customer;
//        });
//
//    }
//
//
//    public function updateAccountStatus(Customer $customer,AccountStatus $status , string $reason = ''): Customer
//    {
//        return DB::transaction(function () use ($customer, $status, $reason) {
//            if ($customer->approval_status == $status->value) {
//                return $customer;
//            }
//            $customer->account_status = $status;
//            $customer->reason = $reason;
//            $customer->save();
//            return $customer;
//        });
//
//    }

    public function createProgramWithVideos(array $data): Program
    {
        return DB::transaction(function () use ($data) {

            // 1️⃣ معالجة صورة الغلاف
            if (isset($data['cover_image']) && is_file($data['cover_image'])) {
                $uploadService = new UploadService();
                $path = $uploadService->upload(
                    $data['cover_image'],
                    'program_images',
                    'public',
                    'programs'
                );
                $data['cover_image'] = asset('storage/' . $path);
            }

            if (!empty($data['creator_id'])) {
                $data['creator_type'] = \App\Models\Admin::class;
            }

            $data['status'] = $data['status'] ?? 'draft';
            $data['is_approved'] = $data['is_approved'] ?? false;

            $programData = collect($data)->except('videos')->toArray();
            $program = Program::create($programData);

            if (isset($data['videos']) && is_array($data['videos'])) {
                foreach ($data['videos'] as $videoData) {
                    $videoData['program_id'] = $program->id;
                    if (isset($videoData['video_path']) && is_file($videoData['video_path'])) {
                        $uploadService = $uploadService ?? new UploadService();
                        $path = $uploadService->upload(
                            $videoData['video_path'],
                            'program_videos',
                            'public',
                            'videos'
                        );
                        $videoData['video_path'] = asset('storage/' . $path);
                    }

                    ProgramVideos::create($videoData);
                }
            }

            $program->load(['videos', 'creator']);

            return $program;
        });
    }

    public function approveProgram(Program $program): Program
    {
        if ($program->is_approved) {
            throw new \Exception(__('messages.PROGRAM_ALREADY_APPROVED'));
        }

        if ($program->status === ProgramStatus::Archived->value) {
            throw new \Exception(__('messages.CANNOT_APPROVE_ARCHIVED_PROGRAM'));
        }

        if ($program->status === ProgramStatus::Draft->value) {
            throw new \Exception(__('messages.CANNOT_APPROVE_DRAFT_PROGRAM'));
        }


        $program->update([
            'is_approved' => true,
            'status' => 'published',
        ]);

        return $program->fresh();
    }

    public function updateProgramWithVideos(Program $program,  $request): Program
    {
        return DB::transaction(function () use ($program, $request) {

            // تحديث صورة الغلاف لو موجودة
            if ($request->hasFile('cover_image')) {
                $uploadService = new UploadService();
                $path = $uploadService->upload(
                    $request->file('cover_image'),
                    'program_images',
                    'public',
                    'programs'
                );

                $data['cover_image'] = asset('storage/' . $path);
            }

            if (!empty($data['creator_id'])) {
                $data['creator_type'] = \App\Models\Admin::class;
            }
            $program->update($data);

            $program->load(['videos', 'creator']);

            return $program;
        });
    }

}
