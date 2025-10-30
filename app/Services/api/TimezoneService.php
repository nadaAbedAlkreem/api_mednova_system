<?php
namespace App\Services\api;

use App\Events\ConsultationRequested;
use App\Models\AppointmentRequest;
use App\Models\ConsultationChatRequest;
use App\Models\ConsultationVideoRequest;
use App\Models\Customer;
use App\Models\Schedule;
use App\Models\User;
use App\Repositories\IAppointmentRequestRepositories;
use App\Repositories\IConsultationChatRequestRepositories;
use App\Repositories\IConsultationVideoRequestRepositories;
use App\Repositories\ICustomerRepositories;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TimezoneService
{

    /**
     * تحويل وقت معين حسب توقيت المستخدم
     */
    public static function toUserTimezone($datetime, ?string $timezone, string $format = 'Y-m-d H:i')
    {
        $timezone = $timezone ?? config('app.timezone');
          return $datetime
            ?$datetime->copy()->setTimezone($timezone)
             : null;
    }

    /**
     * تحويل وقت من توقيت المستخدم إلى UTC (عند الحفظ)
     */
    public static function toUTC($datetime, ?string $timezone)
    {
        $timezone = $timezone ?? config('app.timezone');
        return $datetime
            ?   \Carbon\Carbon::createFromFormat('Y-m-d H:i',$datetime, $timezone)
                ->setTimezone('UTC')
            : null;

    }
    public static function toUTCHour($datetime, ?string $timezone)
    {
        $timezone = $timezone ?? config('app.timezone');
        return $datetime
            ?   \Carbon\Carbon::createFromFormat('H:i',$datetime, $timezone)
                ->setTimezone('UTC')->format('H:i')
            : null;

    }


}
