<?php

namespace App\Listeners;

use App\Events\ScheduleReminderTriggered;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendScheduleReminderNotification implements ShouldQueue
{
    public function __construct(private NotificationService $notificationService)
    {
    }

    public function handle(ScheduleReminderTriggered $event): void
    {
        $this->notificationService->notifyScheduleReminder(
            $event->schedule,
            $event->timing
        );
    }
}
