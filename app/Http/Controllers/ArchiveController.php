<?php

namespace App\Http\Controllers;

use App\Models\Arsip;
use App\Services\ArchiveService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * ArchiveController
 * 
 * Mengelola operasi pengarsipan dokumen, jadwal, dan laporan.
 */
class ArchiveController extends Controller
{
    public function __construct(private ArchiveService $archiveService)
    {
    }

    /**
     * Menampilkan daftar arsip.
     *
     * @param Request $request
     * @return View
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
                : Arsip::orderBy('archive_date', 'desc')->limit(100)->get();
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
     * Menampilkan detail arsip.
     *
     * @param Arsip $arsip
     * @return View
     */
    public function show(Arsip $arsip): View
    {
        $archiveModel = $arsip->arsipkan;

        return view('archives.show', [
            'archive' => $arsip,
            'model' => $archiveModel,
        ]);
    }

    /**
     * Menambahkan tag ke arsip.
     *
     * @param Request $request
     * @param Arsip $arsip
     * @return RedirectResponse
     */
    public function addTags(Request $request, Arsip $arsip): RedirectResponse
    {
        $validated = $request->validate([
            'tags' => 'required|array',
            'tags.*' => 'string|max:50',
        ]);

        $this->archiveService->addTags($arsip, $validated['tags']);

        return back()->with('success', 'Tag berhasil ditambahkan!');
    }

    /**
     * Pencarian arsip.
     *
     * @param Request $request
     * @return View
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
     * Statistik arsip.
     *
     * @return View
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
     * Menghapus arsip (soft delete).
     *
     * @param Arsip $arsip
     * @return RedirectResponse
     */
    public function destroy(Arsip $arsip): RedirectResponse
    {
        $arsip->delete();

        return redirect()->route('archives.index')
            ->with('success', 'Arsip berhasil dihapus!');
    }

    /**
     * Memulihkan arsip yang dihapus.
     *
     * @param int $id
     * @return RedirectResponse
     */
    public function restore(int $id): RedirectResponse
    {
        Arsip::withTrashed()->findOrFail($id)->restore();

        return back()->with('success', 'Arsip berhasil dipulihkan!');
    }
}
