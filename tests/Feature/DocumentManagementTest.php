<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocumentManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles and permissions
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    #[Test]
    public function user_can_view_documents_list_with_permission()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'batih_staf')->first();
        $user->roles()->attach($role);

        $document = Document::factory()->biasa()->create();

        $response = $this->actingAs($user)->get(route('documents.index'));

        $response->assertStatus(200);
        $response->assertSee($document->number);
    }

    #[Test]
    public function user_cannot_view_documents_without_permission()
    {
        $user = User::factory()->create();
        // User without any role/permission

        $response = $this->actingAs($user)->get(route('documents.index'));

        $response->assertStatus(403);
    }

    #[Test]
    public function user_can_search_documents_by_number_and_subject()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'batih_staf')->first();
        $user->roles()->attach($role);

        $document1 = Document::factory()->biasa()->create(['subject' => 'Meeting Minutes', 'created_by' => $user->id]);
        $document2 = Document::factory()->biasa()->create(['subject' => 'Project Report', 'created_by' => $user->id]);

        $response = $this->actingAs($user)->get(route('documents.index', ['search' => 'Meeting']));

        $response->assertStatus(200);
        $response->assertSee('Meeting Minutes');
        $response->assertDontSee('Project Report');
    }

    #[Test]
    public function user_can_filter_documents_by_type()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'batih_staf')->first();
        $user->roles()->attach($role);

        $incomingDoc = Document::factory()->incoming()->create(['created_by' => $user->id]);
        $outgoingDoc = Document::factory()->outgoing()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->get(route('documents.index', ['type' => 'masuk']));

        $response->assertStatus(200);
        $response->assertSee($incomingDoc->number);
        $response->assertDontSee($outgoingDoc->number);
    }

    #[Test]
    public function user_can_filter_documents_by_classification()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'batih_staf')->first();
        $user->roles()->attach($role);

        $biasaDoc = Document::factory()->biasa()->create(['created_by' => $user->id]);
        $rahasiaDoc = Document::factory()->rahasia()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->get(route('documents.index', ['classification' => 'biasa']));

        $response->assertStatus(200);
        $response->assertSee($biasaDoc->number);
    }

    #[Test]
    public function user_can_filter_documents_by_status()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'batih_staf')->first();
        $user->roles()->attach($role);

        $draftDoc = Document::factory()->draft()->create(['created_by' => $user->id]);
        $approvedDoc = Document::factory()->approved()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->get(route('documents.index', ['status' => 'draft']));

        $response->assertStatus(200);
        $response->assertSee($draftDoc->number);
    }

    #[Test]
    public function user_can_view_create_document_form()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'batih_staf')->first();
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->get(route('documents.create'));

        $response->assertStatus(200);
        $response->assertSee('Buat Dokumen Baru');
    }

    #[Test]
    public function user_cannot_view_create_form_without_permission()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('documents.create'));

        $response->assertStatus(403);
    }

    #[Test]
    public function user_can_create_document_with_valid_data()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'batih_staf')->first();
        $user->roles()->attach($role);

        $documentData = [
            'type' => 'masuk',
            'classification' => 'biasa',
            'number' => 'DOC-TEST-001',
            'date' => now()->format('Y-m-d'),
            'sender' => 'Test Sender',
            'subject' => 'Test Subject',
            'description' => 'Test Description',
            'priority' => 'normal',
        ];

        $response = $this->actingAs($user)->post(route('documents.store'), $documentData);

        $response->assertRedirect();
        $this->assertDatabaseHas('documents', [
            'number' => 'DOC-TEST-001',
            'subject' => 'Test Subject',
        ]);
    }

    #[Test]
    public function document_number_is_auto_generated_if_not_provided()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'batih_staf')->first();
        $user->roles()->attach($role);

        $documentData = [
            'type' => 'masuk',
            'classification' => 'biasa',
            'date' => now()->format('Y-m-d'),
            'sender' => 'Test Sender',
            'subject' => 'Test Subject',
            'description' => 'Test Description',
            'priority' => 'normal',
        ];

        $response = $this->actingAs($user)->post(route('documents.store'), $documentData);

        $document = Document::latest()->first();
        $this->assertNotNull($document->number);
        $this->assertStringContainsString('DOC', $document->number);
    }

    #[Test]
    public function classified_document_is_encrypted_automatically()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'batih_staf')->first();
        $user->roles()->attach($role);

        $documentData = [
            'type' => 'masuk',
            'classification' => 'rahasia',
            'date' => now()->format('Y-m-d'),
            'sender' => 'Secret Sender',
            'subject' => 'Secret Subject',
            'description' => 'Secret Description',
            'priority' => 'high',
        ];

        $response = $this->actingAs($user)->post(route('documents.store'), $documentData);

        $document = Document::latest()->first();
        $this->assertTrue($document->is_encrypted);
        $this->assertNotEquals('Secret Description', $document->getRawOriginal('description'));
    }

    #[Test]
    public function document_creation_validates_required_fields()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'batih_staf')->first();
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->post(route('documents.store'), []);

        $response->assertSessionHasErrors(['type', 'classification', 'date', 'subject']);
    }

    #[Test]
    public function incoming_document_requires_sender()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'batih_staf')->first();
        $user->roles()->attach($role);

        $documentData = [
            'type' => 'masuk',
            'classification' => 'biasa',
            'date' => now()->format('Y-m-d'),
            'subject' => 'Test Subject',
            'description' => 'Test Description',
        ];

        $response = $this->actingAs($user)->post(route('documents.store'), $documentData);

        $response->assertSessionHasErrors(['sender']);
    }

    #[Test]
    public function outgoing_document_requires_recipient()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'batih_staf')->first();
        $user->roles()->attach($role);

        $documentData = [
            'type' => 'keluar',
            'classification' => 'biasa',
            'date' => now()->format('Y-m-d'),
            'subject' => 'Test Subject',
            'description' => 'Test Description',
        ];

        $response = $this->actingAs($user)->post(route('documents.store'), $documentData);

        $response->assertSessionHasErrors(['recipient']);
    }

    #[Test]
    public function user_can_view_document_details()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'batih_staf')->first();
        $user->roles()->attach($role);

        $document = Document::factory()->biasa()->create();

        $response = $this->actingAs($user)->get(route('documents.show', $document));

        $response->assertStatus(200);
        $response->assertSee($document->number);
        $response->assertSee($document->subject);
    }

    #[Test]
    public function user_can_view_edit_document_form()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'batih_staf')->first();
        $user->roles()->attach($role);

        $document = Document::factory()->draft()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->get(route('documents.edit', $document));

        $response->assertStatus(200);
        $response->assertSee($document->subject);
    }

    #[Test]
    public function user_cannot_edit_approved_document()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'batih_staf')->first();
        $user->roles()->attach($role);

        $document = Document::factory()->approved()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->get(route('documents.edit', $document));

        $response->assertStatus(403);
    }

    #[Test]
    public function user_can_update_draft_document()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'batih_staf')->first();
        $user->roles()->attach($role);

        $document = Document::factory()->draft()->create(['created_by' => $user->id]);

        $updateData = [
            'type' => $document->type,
            'classification' => $document->classification,
            'number' => $document->number,
            'date' => $document->date->format('Y-m-d'),
            'sender' => $document->sender,
            'subject' => 'Updated Subject',
            'description' => 'Updated Description',
            'priority' => $document->priority,
        ];

        $response = $this->actingAs($user)->put(route('documents.update', $document), $updateData);

        $response->assertRedirect(route('documents.show', $document));
        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'subject' => 'Updated Subject',
        ]);
    }

    #[Test]
    public function user_can_delete_draft_document()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'batih_staf')->first();
        $user->roles()->attach($role);

        $document = Document::factory()->draft()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->delete(route('documents.destroy', $document));

        $response->assertRedirect();
        $this->assertSoftDeleted('documents', ['id' => $document->id]);
    }

    #[Test]
    public function user_cannot_delete_approved_document()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'batih_staf')->first();
        $user->roles()->attach($role);

        $document = Document::factory()->approved()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->delete(route('documents.destroy', $document));

        $response->assertStatus(403);
    }

    #[Test]
    public function user_can_submit_draft_for_approval()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'batih_staf')->first();
        $user->roles()->attach($role);

        $document = Document::factory()->draft()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->post(route('documents.submit', $document));

        $response->assertRedirect(route('documents.show', $document));
        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'status' => 'pending_approval',
        ]);
    }

    #[Test]
    public function user_can_archive_approved_document()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'batih_staf')->first();
        $user->roles()->attach($role);

        $document = Document::factory()->approved()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->post(route('documents.archive', $document));

        $response->assertRedirect(route('documents.show', $document));
        $document->refresh();
        $this->assertNotNull($document->archived_at);
    }

    #[Test]
    public function document_model_has_correct_relationships()
    {
        $creator = User::factory()->create();
        $document = Document::factory()->create(['created_by' => $creator->id]);

        $this->assertInstanceOf(User::class, $document->creator);
        $this->assertEquals($creator->id, $document->creator->id);
    }

    #[Test]
    public function document_model_scopes_work_correctly()
    {
        $user = User::factory()->create();

        // Clear any existing documents first
        Document::query()->forceDelete();

        $draftDoc = Document::factory()->draft()->create(['created_by' => $user->id]);
        $approvedDoc = Document::factory()->approved()->create(['created_by' => $user->id]);
        $incomingDoc = Document::factory()->incoming()->create(['created_by' => $user->id]);
        $rahasiaDoc = Document::factory()->rahasia()->create(['created_by' => $user->id]);

        $this->assertGreaterThanOrEqual(1, Document::draft()->count());
        $this->assertGreaterThanOrEqual(1, Document::approved()->count());
        $this->assertGreaterThanOrEqual(1, Document::ofType('masuk')->count());
        $this->assertGreaterThanOrEqual(1, Document::ofClassification('rahasia')->count());
    }

    #[Test]
    public function document_status_helpers_work_correctly()
    {
        $draftDoc = Document::factory()->draft()->create();
        $approvedDoc = Document::factory()->approved()->create();
        $archivedDoc = Document::factory()->archived()->create();

        $this->assertTrue($draftDoc->isDraft());
        $this->assertFalse($draftDoc->isApproved());
        
        $this->assertTrue($approvedDoc->isApproved());
        $this->assertFalse($approvedDoc->isDraft());
        
        $this->assertTrue($archivedDoc->isArchived());
    }

    #[Test]
    public function document_type_helpers_work_correctly()
    {
        $incomingDoc = Document::factory()->incoming()->create();
        $outgoingDoc = Document::factory()->outgoing()->create();

        $this->assertTrue($incomingDoc->isIncoming());
        $this->assertFalse($incomingDoc->isOutgoing());
        
        $this->assertTrue($outgoingDoc->isOutgoing());
        $this->assertFalse($outgoingDoc->isIncoming());
    }

    #[Test]
    public function document_classification_helpers_work_correctly()
    {
        $biasaDoc = Document::factory()->biasa()->create();
        $rahasiaDoc = Document::factory()->rahasia()->create();
        $telegramDoc = Document::factory()->telegram()->create();

        $this->assertFalse($biasaDoc->isClassified());
        $this->assertFalse($biasaDoc->isTelegram());
        
        $this->assertTrue($rahasiaDoc->isClassified());
        $this->assertFalse($rahasiaDoc->isTelegram());
        
        $this->assertTrue($telegramDoc->isTelegram());
    }

    #[Test]
    public function document_displays_correct_labels()
    {
        $document = Document::factory()->incoming()->biasa()->draft()->create();

        $this->assertEquals('Surat Masuk', $document->getTypeLabel());
        $this->assertEquals('Biasa', $document->getClassificationLabel());
        $this->assertEquals('Draft', $document->getStatusLabel());
        $this->assertEquals('Normal', $document->getPriorityLabel());
    }

    #[Test]
    public function user_with_classified_permission_can_view_classified_documents()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'kasi_kaur')->first();
        $user->roles()->attach($role);

        $rahasiaDoc = Document::factory()->rahasia()->create();

        $response = $this->actingAs($user)->get(route('documents.show', $rahasiaDoc));

        $response->assertStatus(200);
    }

    #[Test]
    public function audit_trail_records_document_creation()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'batih_staf')->first();
        $user->roles()->attach($role);

        $documentData = [
            'type' => 'masuk',
            'classification' => 'biasa',
            'date' => now()->format('Y-m-d'),
            'sender' => 'Test Sender',
            'subject' => 'Test Subject',
            'description' => 'Test Description',
            'priority' => 'normal',
        ];

        $this->actingAs($user)->post(route('documents.store'), $documentData);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Document::class,
            'event' => 'created',
            'user_id' => $user->id,
        ]);
    }
}
