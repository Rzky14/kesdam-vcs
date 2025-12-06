<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use App\Models\Document;
use App\Models\Schedule;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class ReportingTest extends TestCase
{
    use RefreshDatabase;

    private ReportService $reportService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->reportService = app(ReportService::class);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /**
     * Test can view reports index
     */
    public function test_can_view_reports_index(): void
    {
        Report::factory()->count(3)->create();

        $response = $this->get(route('reports.index'));

        $response->assertStatus(200);
        $response->assertViewIs('reports.index');
        $response->assertViewHas('reports');
    }

    /**
     * Test can view single report
     */
    public function test_can_view_single_report(): void
    {
        $report = Report::factory()->create();

        $response = $this->get(route('reports.show', $report));

        $response->assertStatus(200);
        $response->assertViewIs('reports.show');
        $response->assertViewHas('report');
    }

    /**
     * Test can view schedule report generation form
     */
    public function test_can_view_schedule_report_form(): void
    {
        $response = $this->get(route('reports.create.schedule'));

        $response->assertStatus(200);
        $response->assertViewIs('reports.generate-schedule');
    }

    /**
     * Test can generate schedule report
     */
    public function test_can_generate_schedule_report(): void
    {
        // Create some schedules
        Schedule::factory()->count(5)->create([
            'start_date' => now()->subDays(15),
            'end_date' => now()->subDays(14),
        ]);

        $response = $this->post(route('reports.store.schedule'), [
            'period_start' => now()->subMonth()->format('Y-m-d'),
            'period_end' => now()->format('Y-m-d'),
            'schedule_type' => null,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('reports', [
            'type' => 'schedule',
            'status' => 'generated',
        ]);
    }

    /**
     * Test can view document report generation form
     */
    public function test_can_view_document_report_form(): void
    {
        $response = $this->get(route('reports.create.document'));

        $response->assertStatus(200);
        $response->assertViewIs('reports.generate-document');
    }

    /**
     * Test can generate document report
     */
    public function test_can_generate_document_report(): void
    {
        // Create some documents
        Document::factory()->count(10)->create([
            'created_at' => now()->subDays(10),
        ]);

        $response = $this->post(route('reports.store.document'), [
            'period_start' => now()->subMonth()->format('Y-m-d'),
            'period_end' => now()->format('Y-m-d'),
            'document_type' => null,
            'classification' => null,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('reports', [
            'type' => 'document',
            'status' => 'generated',
        ]);
    }

    /**
     * Test schedule report generation validates required fields
     */
    public function test_schedule_report_validates_required_fields(): void
    {
        $response = $this->post(route('reports.store.schedule'), []);

        $response->assertSessionHasErrors(['period_start', 'period_end']);
    }

    /**
     * Test document report generation validates required fields
     */
    public function test_document_report_validates_required_fields(): void
    {
        $response = $this->post(route('reports.store.document'), []);

        $response->assertSessionHasErrors(['period_start', 'period_end']);
    }

    /**
     * Test report service generates schedule report correctly
     */
    public function test_report_service_generates_schedule_report(): void
    {
        Schedule::factory()->count(5)->create([
            'type' => 'dukkes',
            'start_date' => now()->subDays(15),
            'end_date' => now()->subDays(14),
        ]);

        $report = $this->reportService->generateScheduleReport(
            Carbon::now()->subMonth(),
            Carbon::now(),
            null,
            $this->user->id
        );

        $this->assertNotNull($report);
        $this->assertEquals('schedule', $report->type);
        $this->assertEquals('generated', $report->status);
        $this->assertArrayHasKey('total_schedules', $report->data);
    }

    /**
     * Test report service generates document report correctly
     */
    public function test_report_service_generates_document_report(): void
    {
        Document::factory()->count(10)->create([
            'type' => 'masuk',
            'classification' => 'biasa',
            'created_at' => now()->subDays(10),
        ]);

        $report = $this->reportService->generateDocumentReport(
            Carbon::now()->subMonth(),
            Carbon::now(),
            null,
            null
        );

        $this->assertNotNull($report);
        $this->assertEquals('document', $report->type);
        $this->assertEquals('generated', $report->status);
        $this->assertArrayHasKey('total_documents', $report->data);
    }

    /**
     * Test can filter reports by type
     */
    public function test_can_filter_reports_by_type(): void
    {
        Report::factory()->schedule()->count(2)->create();
        Report::factory()->document()->count(3)->create();

        $response = $this->get(route('reports.index', ['type' => 'schedule']));

        $response->assertStatus(200);
        $response->assertViewHas('selectedType', 'schedule');
    }

    /**
     * Test can delete report
     */
    public function test_can_delete_report(): void
    {
        $report = Report::factory()->create();

        $response = $this->delete(route('reports.destroy', $report));

        $response->assertRedirect();
        $this->assertDatabaseMissing('reports', ['id' => $report->id]);
    }

    /**
     * Test report data structure for schedule
     */
    public function test_schedule_report_data_structure(): void
    {
        Schedule::factory()->count(3)->create([
            'type' => 'dukkes',
            'status' => 'active',
            'start_date' => now()->subDays(5),
            'end_date' => now()->subDays(4),
        ]);

        $report = $this->reportService->generateScheduleReport(
            Carbon::now()->subMonth(),
            Carbon::now()
        );

        $data = $report->data;
        $this->assertArrayHasKey('total_schedules', $data);
        $this->assertArrayHasKey('by_type', $data);
        $this->assertArrayHasKey('by_status', $data);
        $this->assertArrayHasKey('average_duration', $data);
        $this->assertArrayHasKey('schedules', $data);
    }

    /**
     * Test report data structure for document
     */
    public function test_document_report_data_structure(): void
    {
        Document::factory()->count(5)->create([
            'type' => 'masuk',
            'classification' => 'biasa',
            'status' => 'approved',
            'created_at' => now()->subDays(5),
        ]);

        $report = $this->reportService->generateDocumentReport(
            Carbon::now()->subMonth(),
            Carbon::now()
        );

        $data = $report->data;
        $this->assertArrayHasKey('total_documents', $data);
        $this->assertArrayHasKey('by_type', $data);
        $this->assertArrayHasKey('by_classification', $data);
        $this->assertArrayHasKey('by_status', $data);
        $this->assertArrayHasKey('approval_rate', $data);
        $this->assertArrayHasKey('documents', $data);
    }
}
