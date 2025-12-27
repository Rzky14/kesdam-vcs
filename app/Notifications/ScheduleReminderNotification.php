<?php

namespace App\Notifications;

use App\Models\Schedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ScheduleReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Schedule $schedule;
    protected int $hoursUntilStart;

    /**
     * Create a new notification instance.
     */
    public function __construct(Schedule $schedule, int $hoursUntilStart = 24)
    {
        $this->schedule = $schedule;
        $this->hoursUntilStart = $hoursUntilStart;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable->hasInAppNotificationEnabled('schedule_reminder')) {
            $channels[] = 'database';
        }

        if ($notifiable->hasEmailNotificationEnabled('schedule_reminder')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reminder: Upcoming Schedule - ' . $this->schedule->title)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('This is a reminder for your upcoming schedule:')
            ->line('**' . $this->schedule->title . '**')
            ->line('Type: ' . $this->schedule->type_label)
            ->line('Start: ' . $this->schedule->start_date->format('d M Y H:i'))
            ->line('Location: ' . ($this->schedule->location ?? 'N/A'))
            ->line('The schedule will start in ' . $this->hoursUntilStart . ' hours.')
            ->action('View Schedule', route('schedules.show', $this->schedule->id))
            ->line('Please make sure you are prepared.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'schedule_reminder',
            'schedule_id' => $this->schedule->id,
            'schedule_title' => $this->schedule->title,
            'schedule_type' => $this->schedule->type,
            'schedule_type_label' => $this->schedule->type_label,
            'start_date' => $this->schedule->start_date->toISOString(),
            'hours_until_start' => $this->hoursUntilStart,
            'location' => $this->schedule->location,
            'message' => "Reminder: '{$this->schedule->title}' starts in {$this->hoursUntilStart} hours",
            'url' => route('schedules.show', $this->schedule->id),
        ];
    }
}



