<?php

namespace App\Console\Commands;

use App\Models\GloveCommand;
use App\Models\GloveData;
use App\Models\GloveError;
use App\Repositories\IGloveErrorRepositories;
use Illuminate\Console\Command;
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
        $glovesData = GloveData::with('glove')->select('created_at','glove_id')->orderBy('created_at' ,'DESC')->get();

        foreach ($glovesData as $gloveData) {
            $diff = $gloveData->created_at->diffInSeconds($now);
            Log::info("diff" . $diff);
            if ($diff >= 2) {
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
        $pendingCommands = GloveCommand::where('ack_status_device_response', 'pending')
            ->where('sent_at', '<', now()->subSeconds(60))
            ->get();

        foreach ($pendingCommands as $command) {
            $command->update(['ack_status_device_response' => 'failed' , 'ack_received_device_response_at' => $command->ack_status]);
            $this->gloveErrorRepositories->storeGloveError(
                'No response from glove after timeout',
                $command->glove_id,
                $command->id,
                GloveError::COMMAND_TIMEOUT
            );
        }



    }
}
