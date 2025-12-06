<?php

namespace App\Listeners;

use App\Events\DocumentApproved;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendDocumentApprovedNotification implements ShouldQueue
{
    public function __construct(private NotificationService $notificationService)
    {
    }

    public function handle(DocumentApproved $event): void
    {
        $this->notificationService->notifyDocumentStatusChange(
            $event->document,
            'approved'
        );
    }
}
