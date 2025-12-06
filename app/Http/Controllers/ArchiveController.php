<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Services\ArchiveService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ArchiveController extends Controller
{
    public function __construct(private ArchiveService $archiveService)
    {
    }

    /**
     * Display archives listing
     */
    public function index(Request $request): View
    {
        $category = $request->query('category');
        $search = $request->query('search');

        if ($search) {
            $archives = $this->archiveService->search($search, $category, 100);
        } else {
            $archives = $category 
                ? $this->archiveService->getByCategory($category, 100)
                : Archive::orderBy('archive_date', 'desc')->limit(100)->get();
        }

        $stats = $this->archiveService->getStatistics();

        return view('archives.index', [
            'archives' => $archives,
            'selectedCategory' => $category,
            'searchTerm' => $search,
            'stats' => $stats,
            'categories' => ['schedule' => 'Jadwal', 'document' => 'Dokumen', 'report' => 'Laporan'],
        ]);
    }

    /**
     * Show archive details
     */
    public function show(Archive $archive): View
    {
        $archiveModel = $archive->archiveable;

        return view('archives.show', [
            'archive' => $archive,
            'model' => $archiveModel,
        ]);
    }

    /**
     * Add tags to archive
     */
    public function addTags(Request $request, Archive $archive): RedirectResponse
    {
        $validated = $request->validate([
            'tags' => 'required|array',
            'tags.*' => 'string|max:50',
        ]);

        $this->archiveService->addTags($archive, $validated['tags']);

        return back()->with('success', 'Tag berhasil ditambahkan!');
    }

    /**
     * Search archives
     */
    public function search(Request $request): View
    {
        $term = $request->query('q', '');
        $category = $request->query('category');

        $archives = $term 
            ? $this->archiveService->search($term, $category, 100)
            : collect();

        return view('archives.search', [
            'archives' => $archives,
            'searchTerm' => $term,
            'selectedCategory' => $category,
            'categories' => ['schedule' => 'Jadwal', 'document' => 'Dokumen', 'report' => 'Laporan'],
        ]);
    }

    /**
     * Get archive statistics
     */
    public function statistics(): View
    {
        $stats = $this->archiveService->getStatistics();
        $expiringArchives = $this->archiveService->getRetentionExpiringArchives(30);

        return view('archives.statistics', [
            'stats' => $stats,
            'expiringArchives' => $expiringArchives,
        ]);
    }

    /**
     * Delete archive (soft delete)
     */
    public function destroy(Archive $archive): RedirectResponse
    {
        $archive->delete();

        return redirect()->route('archives.index')
            ->with('success', 'Arsip berhasil dihapus!');
    }

    /**
     * Restore deleted archive
     */
    public function restore(int $id): RedirectResponse
    {
        Archive::withTrashed()->findOrFail($id)->restore();

        return back()->with('success', 'Arsip berhasil dipulihkan!');
    }
}
