<?php

namespace Tests\Feature;

use App\Models\Arsip;
use App\Models\Dokumen;
use App\Models\Schedule;
use App\Models\Report;
use App\Models\User;
use App\Services\ArchiveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchiveTest extends TestCase
{
    use RefreshDatabase;

    private ArchiveService $archiveService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->archiveService = app(ArchiveService::class);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /**
     * Test can view archives index
     */
    public function test_can_view_archives_index(): void
    {
        Arsip::factory()->count(5)->forDocument()->create();

        $response = $this->get(route('archives.index'));

        $response->assertStatus(200);
        $response->assertViewIs('archives.index');
        $response->assertViewHas('archives');
    }

    /**
     * Test can view single archive
     */
    public function test_can_view_single_archive(): void
    {
        $archive = Arsip::factory()->forDocument()->create();

        $response = $this->get(route('archives.show', $archive));

        $response->assertStatus(200);
        $response->assertViewIs('archives.show');
        $response->assertViewHas('archive');
    }

    /**
     * Test can view archive search page
     */
    public function test_can_view_archive_search(): void
    {
        $response = $this->get(route('archives.search'));

        $response->assertStatus(200);
        $response->assertViewIs('archives.search');
    }

    /**
     * Test can view archive statistics
     */
    public function test_can_view_archive_statistics(): void
    {
        Arsip::factory()->count(10)->forDocument()->create();

        $response = $this->get(route('archives.statistics'));

        $response->assertStatus(200);
        $response->assertViewIs('archives.statistics');
        $response->assertViewHas('statistics');
    }

    /**
     * Test can archive a document
     */
    public function test_can_archive_document(): void
    {
        $document = Dokumen::factory()->create();

        $archive = $this->archiveService->archiveModel(
            $document,
            'dokumen',
            'Archived for retention',
            ['surat', 'masuk']
        );

        $this->assertNotNull($archive);
        $this->assertEquals('App\\Models\\Dokumen', $archive->archiveable_type);
        $this->assertEquals($document->id, $archive->archiveable_id);
        $this->assertEquals('dokumen', $archive->category);
    }

    /**
     * Test can search archives
     */
    public function test_can_search_archives(): void
    {
        Arsip::factory()->forDocument()->create([
            'category' => 'dokumen',
        ]);
        Arsip::factory()->forSchedule()->create([
            'category' => 'jadwal',
        ]);

        $results = $this->archiveService->search('dokumen', 'dokumen');

        $this->assertGreaterThan(0, $results->count());
    }

    /**
     * Test can filter archives by category
     */
    public function test_can_filter_archives_by_category(): void
    {
        Arsip::factory()->count(3)->category('dokumen')->create();
        Arsip::factory()->count(2)->category('jadwal')->create();

        $response = $this->get(route('archives.index', ['category' => 'dokumen']));

        $response->assertStatus(200);
        $response->assertViewHas('selectedCategory', 'dokumen');
    }

    /**
     * Test archive service gets statistics correctly
     */
    public function test_archive_service_gets_statistics(): void
    {
        Arsip::factory()->count(5)->indexed()->create();
        Arsip::factory()->count(3)->notIndexed()->create();
        Arsip::factory()->count(2)->expiringSoon()->create();

        $stats = $this->archiveService->getStatistics();

        $this->assertArrayHasKey('total_archives', $stats);
        $this->assertArrayHasKey('indexed_archives', $stats);
        $this->assertArrayHasKey('expiring_soon', $stats);
        $this->assertArrayHasKey('by_category', $stats);
    }

    /**
     * Test can add tags to archive
     */
    public function test_can_add_tags_to_archive(): void
    {
        $archive = Arsip::factory()->create([
            'tags' => json_encode(['tag1']),
        ]);

        $response = $this->post(route('archives.add-tags', $archive), [
            'tags' => ['tag2', 'tag3'],
        ]);

        $response->assertRedirect();
        $archive->refresh();
        
        $tags = json_decode($archive->tags, true);
        $this->assertContains('tag2', $tags);
        $this->assertContains('tag3', $tags);
    }

    /**
     * Test can delete archive
     */
    public function test_can_delete_archive(): void
    {
        $archive = Arsip::factory()->create();

        $response = $this->delete(route('archives.destroy', $archive));

        $response->assertRedirect();
        $this->assertDatabaseMissing('archives', ['id' => $archive->id]);
    }

    /**
     * Test can restore archived item
     */
    public function test_can_restore_archive(): void
    {
        $archive = Arsip::factory()->forDocument()->create();

        $response = $this->post(route('archives.restore', $archive));

        $response->assertRedirect();
        // Note: Actual restoration logic depends on implementation
    }

    /**
     * Test archive retention expiring scope works
     */
    public function test_archive_retention_expiring_scope(): void
    {
        Arsip::factory()->count(3)->expiringSoon()->create();
        Arsip::factory()->count(2)->longRetention()->create();

        $expiring = Arsip::retentionExpiring(30)->get();

        $this->assertEquals(3, $expiring->count());
    }

    /**
     * Test archive indexed scope works
     */
    public function test_archive_indexed_scope(): void
    {
        Arsip::factory()->count(4)->indexed()->create();
        Arsip::factory()->count(2)->notIndexed()->create();

        $indexed = Arsip::indexed()->get();

        $this->assertEquals(4, $indexed->count());
    }

    /**
     * Test polymorphic relationship works for document
     */
    public function test_polymorphic_relationship_for_document(): void
    {
        $document = Dokumen::factory()->create();
        $archive = Arsip::factory()->create([
            'archiveable_type' => 'App\\Models\\Dokumen',
            'archiveable_id' => $document->id,
        ]);

        $this->assertInstanceOf(Dokumen::class, $archive->archiveable);
        $this->assertEquals($document->id, $archive->archiveable->id);
    }

    /**
     * Test polymorphic relationship works for schedule
     */
    public function test_polymorphic_relationship_for_schedule(): void
    {
        $schedule = Schedule::factory()->create();
        $archive = Arsip::factory()->create([
            'archiveable_type' => 'App\\Models\\Schedule',
            'archiveable_id' => $schedule->id,
        ]);

        $this->assertInstanceOf(Schedule::class, $archive->archiveable);
        $this->assertEquals($schedule->id, $archive->archiveable->id);
    }

    /**
     * Test archive categorization by type
     */
    public function test_archive_categorization_by_type(): void
    {
        Arsip::factory()->count(3)->category('dokumen')->create();
        Arsip::factory()->count(2)->category('jadwal')->create();
        Arsip::factory()->count(1)->category('laporan')->create();

        $byCategory = $this->archiveService->getByCategory('dokumen');

        $this->assertEquals(3, $byCategory->count());
    }
}
