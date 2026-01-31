<?php

namespace App\Jobs;

use App\Models\GloveCommand;
use App\Models\GloveData;
use App\Models\GloveDevice;
use App\Models\GloveSession;
use App\Services\api\Glove\GloveCommandService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExecuteGloveExercise implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected GloveDevice $gloveDevice;
    protected int $customerId;
    protected string $command;
    protected int $repeat;
    protected int $timeRest;
    protected GloveCommandService $service;

    public function __construct(GloveDevice $gloveDevice, int $customerId, string $command, int $repeat = 1, int $timeRest = 60)
    {
        $this->gloveDevice = $gloveDevice;
        $this->customerId = $customerId;
        $this->command = $command;
        $this->repeat = $repeat;
        $this->timeRest = $timeRest;
    }

    public function handle()
    {
        try {
            $this->service = app(GloveCommandService::class);
            $gloveData = GloveData::getLastCorrectDataByCustomer($this->customerId);
            if (!$gloveData) {
                Log::error("No glove data for customer {$this->customerId}");
                throw new \RuntimeException("No glove data found for customer {$this->customerId}");
            }

                $session = GloveSession::create([
                    'glove_id' => $this->gloveDevice->id,
                    'exercise_type' => $this->command,
                    'repetitions_target' => $this->repeat,
                    'interval_between_reps' => $this->timeRest,
                    'default_speed' => 0, // سيتم تحديثه ديناميكيًا
                    'session_start' => now(),
                ]);

            $allSuccessful = true; // <-- لتتبع النجاح والفشل

            for ($i = 1; $i <= $this->repeat; $i++) {
                // جلب آخر البيانات الحيوية قبل كل تكرار

                $gloveData = GloveData::getLastCorrectDataByCustomer($this->customerId);
                $angles = [
                    'thumb' => $gloveData->flex_thumb ?? 0,
                    'index' => $gloveData->flex_index ?? 0,
                    'middle' => $gloveData->flex_middle ?? 0,
                    'ring' => $gloveData->flex_ring ?? 0,
                    'little' => $gloveData->flex_pinky ?? 0,
                ];
                $resistance = $gloveData->resistance ?? 0;
                $averageAngle = array_sum($angles) / count($angles);
                $speed = $this->service->calculateSpeed($averageAngle, $resistance);

                $this->service->sendCommandToPythonJob($this->gloveDevice, $this->command, $session->id, $i, $speed);

                // **هنا الانتظار حتى استلام ACK من Python عبر receiveResponseCommand**
                $ackResult = $this->waitForAck($session->id, $i);
                if ($ackResult === 'failed' || $ackResult === 'timeout') {
                    $allSuccessful = false;
                }

                if ($i < $this->repeat) {
                    sleep($this->timeRest);
                }
            }

            $session->update([
                'repetitions_done' => $this->repeat,
                'session_end' => now(),
                'status' => $allSuccessful ? 'completed' : 'failed',
                'success_rate' => $allSuccessful ? 100 : 0,
            ]);

        }catch (\Exception $exception){
            return  ['status'=> $exception->getMessage() ];
        }

    }

    protected function waitForAck(int $sessionId, int $iteration): string
    {
        $timeout = 30; // ثواني
        $elapsed = 0;
        $interval = 1;

        while ($elapsed < $timeout) {
            $command = GloveCommand::where('session_id', $sessionId)
                ->where('rep_index', $iteration)
                ->latest()
                ->first();
            if ($command) {
                if ($command->ack_status === 'success') {
                    return 'success';
                } elseif ($command->ack_status === 'failed') {
                    Log::error("Iteration {$iteration} failed");
                    return 'failed';
                }
            }
            sleep($interval);
            $elapsed += $interval;
        }

        Log::warning("Iteration {$iteration} timed out waiting for ACK");
        return 'timeout';
    }
}
