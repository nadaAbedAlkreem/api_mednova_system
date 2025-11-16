<?php

namespace App\Console\Commands;

use App\Models\GloveCommand;
use App\Models\GloveError;
use App\Repositories\IGloveErrorRepositories;
use Illuminate\Console\Command;

class CheckPendingGloveCommands extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-pending-glove-commands';

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
        $command = GloveCommand::where('ack_status_device_response', 'pending')
            ->where('sent_at', '<', now()->subSeconds(60))
            ->chunkById(100, function($commands) {
                foreach ($commands as $command) {
                    $command->update([
                        'ack_status_device_response' => 'failed',
                        'ack_received_device_response_at' => now(),
                    ]);
                }
            });
        if($command)
        {
            app(IGloveErrorRepositories::class)->storeGloveError('No response from glove after timeout',
                $command->glove_id, $command->id, GloveError::COMMAND_TIMEOUT);
        }


    }
}
