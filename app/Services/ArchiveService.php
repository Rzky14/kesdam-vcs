<?php

namespace App\Services;

use App\Models\Archive;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ArchiveService
{
    /**
     * Archive a model (Document, Schedule, Report)
     */
    public function archiveModel(
        $model,
        string $category,
        ?string $description = null,
        ?array $tags = null,
        ?Carbon $retentionUntil = null
    ): Archive {
        // Set default retention period (5 years)
        if (!$retentionUntil) {
            $retentionUntil = now()->addYears(5);
        }

        return Archive::create([
            'name' => $model->name ?? $model->subject ?? $model->title ?? 'Archive Item',
            'description' => $description,
            'archiveable_type' => class_basename($model),
            'archiveable_id' => $model->id,
            'archived_by' => Auth::id() ?? 1,
            'archive_date' => now(),
            'retention_until' => $retentionUntil,
            'category' => $category,
            'tags' => $tags ?? [],
            'is_indexed' => false,
        ]);
    }

    /**
     * Search archives
     */
    public function search(
        string $term,
        ?string $category = null,
        ?int $limit = 50
    ): Collection {
        $query = Archive::search($term);

        if ($category) {
            $query->byCategory($category);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Get archives by category
     */
    public function getByCategory(string $category, ?int $limit = 50): Collection
    {
        return Archive::byCategory($category)
            ->orderBy('archive_date', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get all indexed archives
     */
    public function getIndexedArchives(?int $limit = 50): Collection
    {
        return Archive::indexed()
            ->orderBy('archive_date', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Index archives (untuk full-text search)
     */
    public function indexArchives(): int
    {
        $archives = Archive::where('is_indexed', false)
            ->limit(100)
            ->get();

        $count = 0;
        foreach ($archives as $archive) {
            $archive->markAsIndexed();
            $count++;
        }

        return $count;
    }

    /**
     * Get retention expiring archives
     */
    public function getRetentionExpiringArchives(int $days = 30): Collection
    {
        return Archive::retentionExpiring($days)->get();
    }

    /**
     * Permanently delete expired archives
     */
    public function deleteExpiredArchives(): int
    {
        return Archive::where('retention_until', '<', now())
            ->forceDelete();
    }

    /**
     * Add tags to archive
     */
    public function addTags(Archive $archive, array $newTags): Archive
    {
        $existingTags = $archive->tags ?? [];
        $archive->tags = array_unique(array_merge($existingTags, $newTags));
        $archive->save();

        return $archive;
    }

    /**
     * Get archive statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_archives' => Archive::count(),
            'by_category' => Archive::groupBy('category')->selectRaw('category, COUNT(*) as count')->get()->toArray(),
            'indexed_archives' => Archive::indexed()->count(),
            'expiring_soon' => Archive::retentionExpiring(30)->count(),
        ];
    }
}
