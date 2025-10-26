<?php
namespace App\Services\api;

use App\Events\ConsultationRequested;
use App\Models\AppointmentRequest;
use App\Models\ConsultationChatRequest;
use App\Models\Customer;
use App\Models\Schedule;
use App\Models\User;
use App\Repositories\IAppointmentRequestRepositories;
use App\Repositories\IConsultationChatRequestRepositories;
use App\Repositories\IConsultationVideoRequestRepositories;
use App\Repositories\ICustomerRepositories;
use Exception;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GoogleMeetingService
{



    public function createMeetingLinkGoogle($startTime, $endTime , $patientEmail  ,$consultantEmail)
    {
        dd(storage_path('app/private/client_secret_429602649937-7c6jkhcanrdhq7mophnb3ucp745mtcbt.apps.googleusercontent.com.json'));
        return DB::transaction(function () use ($startTime, $endTime , $patientEmail, $consultantEmail) {

            $client = new Google_Client();
            $client->setAuthConfig(storage_path('app/private/client_secret_429602649937-7c6jkhcanrdhq7mophnb3ucp745mtcbt.apps.googleusercontent.com.json'));
            $client->addScope(Google_Service_Calendar::CALENDAR);

            $service = new Google_Service_Calendar($client);

            $event = new Google_Service_Calendar_Event([
                'summary' => 'Consultation Meeting',
                'start' => ['dateTime' => $startTime, 'timeZone' => 'Asia/Amman'],
                'end' => ['dateTime' => $endTime, 'timeZone' => 'Asia/Amman'],
                'attendees' => [
                    ['email' => $patientEmail],
                    ['email' => $consultantEmail],
                ],
                'conferenceData' => [
                    'createRequest' => [
                        'requestId' => uniqid(),
                        'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                    ]
                ]
            ]);

            $event = $service->events->insert('primary', $event, ['conferenceDataVersion' => 1]);

            $meetLink = $event->conferenceData->entryPoints[0]->uri;
            return $meetLink;
        });
    }
    }
