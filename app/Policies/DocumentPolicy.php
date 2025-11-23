<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DocumentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view documents list
        return $user->hasPermission('view_documents');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Document $document): bool
    {
        // Users can view documents if they have permission
        // Classified documents require special permission
        if ($document->isClassified()) {
            return $user->hasPermission('view_classified_documents');
        }

        return $user->hasPermission('view_documents');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Batih/Staf and above can create documents
        return $user->hasPermission('create_documents');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Document $document): bool
    {
        // Admin can update any document
        if ($user->hasRole('Admin Sistem')) {
            return true;
        }

        // Users can only update their own draft or rejected documents
        if ($document->created_by === $user->id && in_array($document->status, ['draft', 'rejected'])) {
            return $user->hasPermission('update_documents');
        }

        // Pimpinan and Kasi/Kaur can update documents for approval workflow
        if ($user->hasAnyRole(['Pimpinan/Pejabat Tinggi', 'Kasi/Kaur'])) {
            return $user->hasPermission('approve_documents');
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Document $document): bool
    {
        // Admin can delete any draft document
        if ($user->hasRole('Admin Sistem')) {
            return $document->isDraft();
        }

        // Users can only delete their own draft documents
        return $document->created_by === $user->id 
            && $document->isDraft() 
            && $user->hasPermission('delete_documents');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Document $document): bool
    {
        // Only admin can restore deleted documents
        return $user->hasRole('Admin Sistem');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Document $document): bool
    {
        // Only admin can permanently delete documents
        return $user->hasRole('Admin Sistem');
    }

    /**
     * Determine whether the user can approve the document.
     */
    public function approve(User $user, Document $document): bool
    {
        // Only Pimpinan and Kasi/Kaur can approve documents
        return $user->hasAnyRole(['Pimpinan/Pejabat Tinggi', 'Kasi/Kaur']) 
            && $user->hasPermission('approve_documents')
            && $document->isPendingApproval();
    }

    /**
     * Determine whether the user can archive the document.
     */
    public function archive(User $user, Document $document): bool
    {
        // Users with archive permission can archive approved documents
        return $user->hasPermission('archive_documents') && $document->isApproved();
    }
}
