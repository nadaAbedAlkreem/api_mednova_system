<?php

namespace App\Services\Api;

use App\Jobs\ExecuteGloveExercise;
use App\Models\Device;
use App\Models\GloveCommand;
use App\Models\GloveData;
use App\Models\GloveDevice;
use App\Models\GloveError;
use App\Models\GloveSession;
use App\Repositories\IGloveCommandRepositories;
use App\Repositories\IGloveDeviceRepositories;
use App\Repositories\IGloveErrorRepositories;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GloveCommandService
{
    protected IGloveDeviceRepositories $gloveDeviceRepo;
    protected IGloveCommandRepositories $gloveCommandRepo;
    protected IGloveErrorRepositories $gloveErrorRepo;
    protected Client $httpClient;

    public function __construct(
        IGloveErrorRepositories $gloveErrorRepo,
        IGloveDeviceRepositories $gloveDeviceRepo,
        IGloveCommandRepositories $gloveCommandRepo
    ) {
        $this->gloveDeviceRepo = $gloveDeviceRepo;
        $this->gloveCommandRepo = $gloveCommandRepo;
        $this->gloveErrorRepo = $gloveErrorRepo;
        $this->httpClient = new Client();
    }

    /**
     * إرسال أي أمر للقفاز الذكي
     */
    public function sendCommandToGlove(string $command, int $customerId, ?int $repeat = 1 , ?int $timeRest = 60): array
    {
        try {
            DB::beginTransaction();
            //   1. التحقق من وجود الجهاز
            $device = Device::where('name_en', 'Smart Glove')->firstOrFail();
            if (!$device->token) {
                return ['status' =>__('messages.device_not_found') ,  'data' => []];
            }

            //   2. جلب أو إنشاء سجل القفاز للمستخدم
            $gloveDevice = $this->gloveDeviceRepo->firstOrCreate([
                'smart_glove_id' => $device->id,
                'customer_id'    => auth()->id(),
            ]);
            $gloveDevice->update(['last_seen_at' => now()]);

            //   3. تحديد نوع الأمر (اتصال أو تمرين)
            if ($this->isConnectionCommand($command)) {
                $responseData = $this->handleConnectionCommand($gloveDevice, $command);
            } else {
                  if($gloveDevice->status == GloveDevice::STATUS_ERROR || $gloveDevice->status == GloveDevice::STATUS_DISCONNECTED  ||$gloveDevice->status == GloveDevice::STATUS_PROGRESS   )
                 {return ['status' => __('messages.connection_not_active') ,  'data' => []];}
                 $responseData = $this->handleExerciseCommand($gloveDevice, $command, $customerId, $repeat , $timeRest);
            }

            DB::commit();
            return $responseData ;

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $this->storeGloveError(
                errorMessage: $e->getMessage(),
                gloveId: $gloveDevice->id ?? null,
                commandId: $command->id ?? null,
                errorType: GloveError::PYTHON_UNREACHABLE
            );
            return  ['status' =>__('messages.glove_pairing_failed') . ' | ' . $e->getMessage() ,  'data' => []];
        } catch (\Exception $e) {
            $this->storeGloveError(
                errorMessage: $e->getMessage(),
                gloveId: $gloveDevice->id ?? null,
                commandId: $command->id ?? null,
                errorType: GloveError::PYTHON_UNREACHABLE
            );
            DB::rollBack();
            return  ['status' => __('messages.glove_pairing_failed') . ' | ' . $e->getMessage() ,  'data' => []];
        }
    }

    /**
     * التحقق من كون الأمر خاص بالاتصال فقط
     */
    private function isConnectionCommand(string $command): bool
    {
        return in_array($command, ['PING']);
    }

    /**
     * معالجة أوامر الاتصال
     */
    private function handleConnectionCommand($gloveDevice, string $command): array
    {
        $payload = ['command'  => $command, 'glove_id' => $gloveDevice->id];
        return $this->sendCommandToPythonInternal($payload, $gloveDevice, $command);
    }
    private function sendCommandToPythonInternal(array $payload, $gloveDevice, string $commandCode, ?int $sessionId = null, ?int $iteration = null, ?float $speed = 0): array
    {
        $command = $this->gloveCommandRepo->create([
            'glove_id'         => $gloveDevice->id,
            'session_id'       => $sessionId,
            'command_code'     => $commandCode,
            'speed'            => $speed,
            'ack_status_send'  => 'pending',
            'rep_index'        => $iteration ?? 0,
            'sent_at'          => now(),
        ]);
        $pythonApiUrl = 'http://127.0.0.1:5000/api/smart-glove-device/send-command';

        try {
            $response = $this->httpClient->post($pythonApiUrl, [
                'headers' => ['Content-Type' => 'application/json'],
                'json'    => $payload,
            ]);

            $data = json_decode($response->getBody(), true);

            if (!isset($data['status'])) {
                $this->storeGloveError('Unexpected response from Python', $gloveDevice->id, $command->id, GloveError::UNKNOWN);
                $command->update(['ack_status_send' => 'failed', 'ack_received_send_at' => now()]);
                return ['status' => __('messages.send_command_failed'), 'data' => $data];
            }

            if ($data['status'] === 'failed') {
                $this->storeGloveError($data['message'] ?? 'Unknown error', $gloveDevice->id, $command->id, GloveError::INVALID_ACK);
                $command->update(['ack_status_send' => 'failed', 'ack_received_send_at' => now()]);
                return ['status' => __('messages.send_command_failed'), 'data' => $data];
            }

            $command->update(['ack_status_send' => 'success', 'ack_received_send_at' => now()]);
            return ['status' => __('messages.send_command_success'), 'data' => $data];

        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            $this->storeGloveError('Python service unreachable: ' . $e->getMessage(), $gloveDevice->id, $command->id, GloveError::PYTHON_UNREACHABLE);
            $command->update(['ack_status_send' => 'failed', 'ack_received_send_at' => now()]);
            return ['status' => __('messages.python_unreachable'), 'data' => []];

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $this->storeGloveError('Request failed: ' . $e->getMessage(), $gloveDevice->id, $command->id, GloveError::UNKNOWN);
            $command->update(['ack_status_send' => 'failed', 'ack_received_send_at' => now()]);
            return ['status' => __('messages.send_command_failed'), 'data' => []];
        }
    }


    /**
     * معالجة أوامر التمارين
     */
    private function handleExerciseCommand($gloveDevice, string $command, int $customerId, int $repeat , int $timeRest): array
    {
        ExecuteGloveExercise::dispatch($gloveDevice, $customerId, $command, $repeat ?? 1, $timeRest ?? 60);
        return [
            'status' => __('messages.exercise_send'),
            'data' => []
        ];
    }

    /**
     * إرسال الطلب إلى Python API
     */

    public function sendCommandToPythonJob($gloveDevice, string $command, $sessionId, $iteration, $speed): array
    {
        $payload = [
            'command'   => $command,
            'glove_id'  => $gloveDevice->id,
            'iteration' => $iteration,
            'speed'     => $speed,
        ];

        return $this->sendCommandToPythonInternal($payload, $gloveDevice, $command, $sessionId, $iteration, $speed);
    }
    /**
     * حساب السرعة بناءً على الزاوية والمقاومة (Pain Limit)
     */
    public function calculateSpeed(float $flexRange, float $resistance): float
    {
        $baseSpeed = 100;       // الحد الأعلى للسرعة
        $maxFlex = 1023;        // أقصى زاوية ممكنة للمستشعرات
        $maxResistance = 255;   // أقصى مقاومة (من 0 إلى 255)

        // تطبيع القيم إلى مدى [0,1]
        $normalizedFlex = min($flexRange / $maxFlex, 1);
        $normalizedPain = min($resistance / $maxResistance, 1);

        // العلاقة العكسية بين الألم والسرعة
        $speed = $baseSpeed * $normalizedFlex * (1 - $normalizedPain);

        // منع القيم السالبة أو الزائدة
        return max(min(round($speed, 2), 100), 0);
    }


    /**
     * تسجيل الأخطاء في قاعدة البيانات
     */
    private function storeGloveError(string $errorMessage, ?int $gloveId , ?int $commandId , ?string $errorType): void
    {
        $this->gloveErrorRepo->storeGloveError($errorMessage, $gloveId ,$commandId , $errorType  );
    }
}
