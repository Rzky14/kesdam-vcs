<?php

namespace App\Events;

use App\Models\Schedule;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScheduleReminderTriggered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Schedule $schedule,
        public string $timing // '1-day', 'day-of', '1-hour'
    ) {
    }
}
