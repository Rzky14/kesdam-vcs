<?php

namespace App\Listeners;

use App\Events\DocumentRejected;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendDocumentRejectedNotification implements ShouldQueue
{
    public function __construct(private NotificationService $notificationService)
    {
    }

    public function handle(DocumentRejected $event): void
    {
        $this->notificationService->notifyDocumentStatusChange(
            $event->document,
            'rejected',
            $event->reason
        );
    }
}
