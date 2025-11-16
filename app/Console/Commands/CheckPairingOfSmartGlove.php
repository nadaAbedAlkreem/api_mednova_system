<?php

namespace App\Console\Commands;

use App\Models\GloveCommand;
use App\Models\GloveData;
use App\Models\GloveError;
use App\Repositories\IGloveErrorRepositories;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckPairingOfSmartGlove extends Command
{
    protected IGloveErrorRepositories  $gloveErrorRepositories;


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-pairing-of-smart-glove';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = now();
        $glovesData = GloveData::with('glove')
            ->whereHas('glove', fn($q) => $q->whereIn('status',['connected','active']))
            ->select('glove_id', DB::raw('MAX(created_at) as created_at'))
            ->groupBy('glove_id')
            ->get();


        foreach ($glovesData as $gloveData) {
            $diff = $gloveData->created_at->diffInSeconds($now);
            Log::info("diff" . $diff);
            if ($diff >= 5000) {
                if($gloveData->glove){
                    $gloveData->glove->update(['status' =>'disconnected']);
                    Log::info("update" . 'disconnected');

                }
                if($gloveData->error_flag && $gloveData->status == 4) {
                    $gloveData->glove->update(['status' =>'error']);
                    Log::info("update" . 'error');
                    $this->gloveErrorRepositories->storeGloveError(
                        'No response from glove after timeout',
                        $gloveData->glove->id,
                        null,
                        GloveError::UNKNOWN
                    );
                }

            }
        }


    }
}
