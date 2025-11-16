<?php

namespace App\Repositories\Eloquent;

use App\Models\GloveDevice;
use App\Models\GloveError;
use App\Repositories\IGloveErrorRepositories;


class GloveErrorRepository  extends BaseRepository implements IGloveErrorRepositories
{
    public function __construct()
    {
        $this->model = new GloveError();
    }

    public function storeGloveError(string $errorMessage, ?int $gloveId = null , ?int $commandId = null  ,?string $errorType = null): void
    {
        logger()->info('Payload received create in store');

        switch ($errorType ?? GloveError::UNKNOWN) {
            case GloveError::PYTHON_UNREACHABLE:
                $normalizedMessage = preg_replace('/\d+/', '', $errorMessage);
                break;
            case GloveError::COMMAND_TIMEOUT:
                // هذه الأخطاء تتغير أرقامها في كل مرة (زمن، منفذ، ...)، لذلك نحذف الأرقام
                $normalizedMessage = preg_replace('/\d+/', '', $errorMessage);
                break;

            default:
                // في باقي الأخطاء، نترك الرسالة كما هي لأن الرقم ممكن يكون مهم
                $normalizedMessage = $errorMessage;
                break;
        }
        logger()->info('Payload received create in error type' . $errorType);


        $errorFlag = crc32($errorType . '_' . $normalizedMessage) % 255;
//        $errorFlag = crc32($errorMessage) % 255;
        $query = $this->model->with('glove')->newQuery()
            ->where('error_flag', $errorFlag);

        if ($gloveId) {
            $query->where('glove_id', $gloveId);
        } else {
            $query->whereNull('glove_id');
        }

        if ($errorType) {
            $query->where('error_type', $errorType);
        } else {
            $query->whereNull('error_type');
        }

        if ($commandId) {
            $query->where('command_id', $commandId);
        } else {
            $query->whereNull('command_id');
        }
         $existingError = $query->first();

        logger()->info('Payload received create in error exist' . $existingError);

        if ($existingError) {
            $existingError->increment('repeat_count');
            if($existingError->repeat_count  > 10 )
            {
                if($existingError->glove->status == GloveDevice::STATUS_CONNECTED || $existingError->glove->status == GloveDevice::STATUS_ACTIVE)
                {
                    $existingError->glove->update(['status' => GloveDevice::STATUS_ERROR]);

                }
                /// مفروض هن نعمل ايقاف لعملية ارسال مؤشرات حيوية
            }else{
                $existingError->update(['last_occurrence' => now()]);

            }
        } else {

             $this->model->create([
                'glove_id'         => $gloveId,
                'command_id'       => $commandId,
                'error_flag'       => $errorFlag,
                'error_message'    => $errorMessage,
                'error_type'       => $errorType,
                'repeat_count'     => 1,
                'first_occurrence' => now(),
                'last_occurrence'  => now(),
            ]);

        }
    }
}
