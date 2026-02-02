<?php
namespace App\Services\Api\Customer;


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

    public static function toLocalHour($utcTime, ?string $timezone)
    {
        return $utcTime
            ? \Carbon\Carbon::createFromFormat('H:i', $utcTime, 'UTC') // الوقت مخزن بـ UTC
            ->setTimezone($timezone) // حوّله إلى timezone المطلوب
            ->format('H:i') // صيغة الساعة والدقيقة
            : null;
    }



}
