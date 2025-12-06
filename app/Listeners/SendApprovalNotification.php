<?php

namespace App\Listeners;

use App\Events\ApprovalRequested;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendApprovalNotification implements ShouldQueue
{
    public function __construct(private NotificationService $notificationService)
    {
    }

    public function handle(ApprovalRequested $event): void
    {
        // Get the users who need to approve at this level
        $workflow = $event->document->getCurrentApprovalLevel();

        if (!$workflow) {
            return;
        }

        // Get approvers for this level
        $approvers = $workflow->rolePermissions()
            ->where('approval_level', $event->approvalLevel)
            ->with('role.users')
            ->get()
            ->flatMap(fn($rp) => $rp->role->users);

        foreach ($approvers as $approver) {
            $this->notificationService->notifyApprovalRequest($event->document, $approver);
        }
    }
}
