<?php

use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\SocialAuthController;
use App\Http\Controllers\Api\Consultation\AppointmentRequestController;
use App\Http\Controllers\Api\Consultation\ConsultationChatRequestController;
use App\Http\Controllers\Api\Consultation\ConsultationController;
use App\Http\Controllers\Api\Consultation\ConsultationVideoRequestController;
use App\Http\Controllers\Api\Consultation\MessageController;
use App\Http\Controllers\Api\Consultation\ScheduleController;
use App\Http\Controllers\Api\Consultation\ZoomWebhookController;
use App\Http\Controllers\Api\Device\DeviceController;
use App\Http\Controllers\Api\Device\DeviceRequestController;
use App\Http\Controllers\Api\Device\GloveCommandController;
use App\Http\Controllers\Api\Device\GloveDataController;
use App\Http\Controllers\Api\Device\GloveDeviceController;
use App\Http\Controllers\Api\Device\GloveErrorController;
use App\Http\Controllers\Api\Program\ProgramController;
use App\Http\Controllers\Api\Program\ProgramEnrollmentController;
use App\Http\Controllers\Api\Program\ProgramVideosController;
use App\Http\Controllers\Api\Customer\CustomerController;
use App\Http\Controllers\Api\Customer\LocationController;
use App\Http\Controllers\Api\Customer\MedicalSpecialtieController;
use App\Http\Controllers\Api\Customer\NotificationsController;
use App\Http\Controllers\Api\Customer\PatientController;
use App\Http\Controllers\Api\Customer\RatingController;
use App\Http\Controllers\Api\Customer\RehabilitationCenterController;
use App\Http\Controllers\Api\Customer\TherapistController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;


Route::prefix('auth')->group(function ()
{
    Route::post('/register', [RegisterController::class, 'register']);
    Route::post('/login', [LoginController::class, 'login'])->name('login-post');
    Route::post('social-callback', [SocialAuthController::class, 'handleSocialLogin']);
    Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
    Route::post('reset-password', [ForgotPasswordController::class, 'resetPassword']);
    Route::post('verifyToken', [ForgotPasswordController::class, 'verifyToken']);
    Route::get('verify-email', [RegisterController::class, 'verifyEmail']);
});
        Route::get('rating', [RatingController::class, 'getTopRatedServiceProvider']);
        Route::prefix('medical-specialties')->group(function ()
        {
            Route::get('', [MedicalSpecialtieController::class, 'getAll']);
            Route::get('/filter', [MedicalSpecialtieController::class, 'getServiceProviderDependMedicalSpecialties']); // not work
        });
        Route::get('programs/show/get-top-enrolled-program', [ProgramEnrollmentController::class, 'getTopEnrolledProgram']);        // نشر البرنامج done
        Route::prefix('customer')->group(function ()
        {
            Route::get('/service-provider/search', [CustomerController::class, 'searchOfServiceProvider']);
            Route::get('/{id}', [CustomerController::class, 'getById']);
            Route::post('update-timezone', [CustomerController::class, 'updateTimezone']);
            Route::get('show/timezone', [CustomerController::class, 'getTimezone']);
        });
        Route::prefix('device')->group(function () {
            Route::get('/', [DeviceController::class, 'get']);
        });

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);
    Broadcast::routes(['middleware' => ['auth:sanctum']]);
    Route::prefix('patient')->group(function ()
    {
        Route::post('/store', [PatientController::class, 'store']);
        Route::post('/update', [PatientController::class, 'update']);

    });


    Route::prefix('therapist')->group(function ()
    {
        Route::get('/', [TherapistController::class, 'get']);
        Route::post('/store', [TherapistController::class, 'store']);
        Route::post('/update', [TherapistController::class, 'update']);
    });

    Route::prefix('schedule')->group(function ()
    {
        Route::post('store', [ScheduleController::class, 'store']);
        Route::post('/update', [ScheduleController::class, 'update']);

    });
    Route::prefix('location')->group(function ()
    {
        Route::post('store', [LocationController::class, 'store']);
        Route::post('update', [LocationController::class, 'update']);
    });
    Route::prefix('consultation-request')->group(function ()
    {
        Route::post('/store', [ConsultationController::class, 'store']);
        Route::get('/get-status-request', [ConsultationController::class, 'getStatusRequest']); // test time for consultant and patient
        Route::post('/update-status-request', [ConsultationController::class, 'updateStatusRequest']);
        Route::prefix('video')->group(function ()
        {
          Route::post('/check-available-slots', [AppointmentRequestController::class, 'checkAvailableSlots']);
        });
//        Route::prefix('chat')->group(function ()
//        {
//            Route::post('update-chatting', [ConsultationChatRequestController::class, 'updateChatting']);
//        });
 //        Route::post('/update-status-chat-request', [ConsultationChatRequestController::class, 'updateStatusRequest']); // توحيد
//        Route::post('/update-status-video-request', [ConsultationVideoRequestController::class, 'updateStatusRequest']);
//
    });
//    Route::post('/zoom/webhooks', [ZoomWebhookController::class, 'handle']);
    Route::prefix('programs')->group(function () {
        Route::get('/', [ProgramController::class, 'getAll']);  //done get all programs for every one service provider
//        Route::get('/current-service-provider', [ProgramController::class, 'getAllProgramsForCurrentProvider']);  //done get all programs for every one service provider
        Route::post('/', [ProgramController::class, 'store']);
        Route::get('{id}', [ProgramController::class, 'show']);
        Route::post('program/update', [ProgramController::class, 'update']);
        Route::delete('{id}', [ProgramController::class, 'destroy']);  //done delete one program
        Route::get('{id}/publish', [ProgramController::class, 'publish']);        // نشر البرنامج done

    });


//        Route::post('{program}/archive', [ProgramController::class, 'archive']);        // أرشفة البرنامج
//
        Route::prefix('/videos')->group(function () {
            Route::post('/store', [ProgramVideosController::class, 'store']);          // إضافة فيديو done
            Route::post('/update', [ProgramVideosController::class, 'update']);     // تعديل فيديوdone
            Route::delete('delete/{videoId}', [ProgramVideosController::class, 'destroy']); // حذف فيديوdone
//            Route::post('order', [ProgramVideosController::class, 'updateOrder']); // تعديل ترتيب الفيديوهات
        });
//
        Route::prefix('{program}/review-requests')->group(function () {
//            Route::get('/', [ProgramReviewRequestController::class, 'index']); // قائمة الطلبات الخاصة بالبرنامج
//            Route::post('', [ProgramReviewRequestsController::class, 'store']);  // إنشاء طلب مراجعةdone
        });
//    });
//
//// إدارة طلبات المراجعة من منظور المشرف
//    Route::prefix('review-requests')->group(function () {
//        Route::get('/', [ProgramReviewRequestController::class, 'all']);           // قائمة كل طلبات المراجعة
//        Route::patch('{request}', [ProgramReviewRequestController::class, 'update']); // الموافقة أو الرفض
//    });
    Route::prefix('notification')->group(function ()
    {
        Route::get('/', [NotificationsController::class, 'getNotificationsForCurrentUser']);
    });
    Route::prefix('messages')->group(function ()
    {
        Route::get('messengers/current-user', [MessageController::class, 'getMessengers']);
        Route::get('{chatId}', [MessageController::class, 'fetchMessages']);
        Route::post('sent', [MessageController::class, 'sendMessage']);
        Route::get('mark-as-read/{senderId}', [MessageController::class, 'markAsRead']);
    });

    Route::prefix('center')->group(function ()
    {
        Route::post('/store', [RehabilitationCenterController::class, 'store']);
        Route::post('/update', [RehabilitationCenterController::class, 'update']);
    });




    Route::prefix('device-request')->group(function () {
        Route::post('/store', [DeviceRequestController::class, 'store']);
        Route::post('/update', [DeviceRequestController::class, 'update']);
    });


    Route::prefix('rating')->group(function ()
    {
        Route::post('store', [RatingController::class, 'store']);
    });
    Route::prefix('smart-glove-device')->group(function () {
        Route::post('send-command', [GloveCommandController::class, 'sendCommand']);
     });

});
//Route::middleware(VerifyDeviceToken::class)->prefix('smart-glove-device-simulation')->group(function () {
//    Route::post('receive-bio-readings', [GloveDataController::class, 'store']);
//});

Route::prefix('smart-glove-device')->group(function () {
    Route::post('feedback-error', [GloveErrorController::class, 'receiveErrorReport']);
    Route::post('store-response-command', [GloveCommandController::class, 'receiveResponseCommand']);
    Route::post('receive-bio-readings', [GloveDataController::class, 'store']);
});

Route::prefix('zoom-webhook')->group(function ()
{
    Route::post('handle',[ZoomWebhookController::class, 'handle']);

});

