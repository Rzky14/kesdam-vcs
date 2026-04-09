<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Crypt;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocumentManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    #[Test]
    public function user_can_view_documents_list_with_permission()
    {
        $user = $this->createUserWithRole('batih');

        Surat::factory()->biasa()->create();

        $response = $this->actingAs($user)->get(route('surat.index'));

        $response->assertStatus(200);
        $response->assertViewIs('surat.index');
    }

    #[Test]
    public function user_can_create_document_with_valid_data()
    {
        $user = $this->createUserWithRole('batih');

        $documentData = [
            'type' => 'masuk',
            'classification' => 'biasa',
            'date' => now()->format('Y-m-d'),
            'sender' => 'Test Sender',
            'subject' => 'Test Subject',
            'description' => 'Test Description',
            'priority' => 'normal',
        ];

        $response = $this->actingAs($user)->post(route('surat.store'), $documentData);

        $response->assertRedirect();
        $this->assertDatabaseHas('documents', [
            'subject' => 'Test Subject',
            'sender' => 'Test Sender',
        ]);
    }

    #[Test]
    public function document_number_is_auto_generated_if_not_provided()
    {
        $user = $this->createUserWithRole('batih');

        $response = $this->actingAs($user)->post(route('surat.store'), [
            'type' => 'masuk',
            'classification' => 'biasa',
            'date' => now()->format('Y-m-d'),
            'sender' => 'Test Sender',
            'subject' => 'Auto Number Subject',
            'description' => 'Test Description',
            'priority' => 'normal',
        ]);

        $response->assertRedirect();

        $document = Surat::latest()->first();
        $this->assertNotNull($document->number);
        $this->assertStringStartsWith('SM', $document->number);
    }

    #[Test]
    public function classified_document_is_encrypted_automatically()
    {
        $user = $this->createUserWithRole('batih');

        $this->actingAs($user)->post(route('surat.store'), [
            'type' => 'masuk',
            'classification' => 'rahasia',
            'date' => now()->format('Y-m-d'),
            'sender' => 'Secret Sender',
            'subject' => 'Secret Subject',
            'description' => 'Secret Description',
            'priority' => 'high',
        ]);

        $document = Surat::latest()->first();
        $this->assertTrue($document->is_encrypted);
        $this->assertNotEquals('Secret Description', $document->getRawOriginal('description'));
    }

    #[Test]
    public function user_can_view_document_details()
    {
        $user = $this->createUserWithRole('batih');
        $document = Surat::factory()->biasa()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->get(route('surat.show', $document));

        $response->assertStatus(200);
        $response->assertViewIs('surat.show');
        $response->assertSee($document->number);
        $response->assertSee($document->subject);
    }

    #[Test]
    public function user_can_view_edit_document_form()
    {
        $user = $this->createUserWithRole('batih');
        $document = Surat::factory()->draft()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->get(route('surat.edit', $document));

        $response->assertStatus(200);
        $response->assertViewIs('surat.edit');
    }

    #[Test]
    public function user_can_update_draft_document()
    {
        $user = $this->createUserWithRole('batih');
        $document = Surat::factory()->incoming()->draft()->biasa()->create(['created_by' => $user->id]);

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

        $response = $this->actingAs($user)->put(route('surat.update', $document), $updateData);

        $response->assertRedirect(route('surat.show', $document));
        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'subject' => 'Updated Subject',
        ]);
    }

    #[Test]
    public function user_can_submit_draft_for_approval()
    {
        $user = $this->createUserWithRole('batih');
        $document = Surat::factory()->draft()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->post(route('surat.submit', $document));

        $response->assertRedirect(route('surat.show', $document));
        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'status' => 'pending_approval',
        ]);
    }

    #[Test]
    public function user_with_classified_permission_can_view_classified_documents()
    {
        $user = $this->createUserWithRole('kasi');
        $document = Surat::factory()->rahasia()->create();

        $response = $this->actingAs($user)->get(route('surat.show', $document));

        $response->assertStatus(200);
    }

    #[Test]
    public function document_model_helpers_work_correctly()
    {
        $draftDocument = Surat::factory()->draft()->biasa()->create(['priority' => 'normal']);
        $approvedDocument = Surat::factory()->approved()->create();
        $archivedDocument = Surat::factory()->archived()->create();
        $incomingDocument = Surat::factory()->incoming()->create();
        $outgoingDocument = Surat::factory()->outgoing()->create();
        $classifiedDocument = Surat::factory()->rahasia()->create();
        $telegramDocument = Surat::factory()->telegram()->create();

        $this->assertTrue($draftDocument->isDraft());
        $this->assertTrue($approvedDocument->isApproved());
        $this->assertTrue($archivedDocument->isArchived());
        $this->assertTrue($incomingDocument->isIncoming());
        $this->assertTrue($outgoingDocument->isOutgoing());
        $this->assertTrue($classifiedDocument->isClassified());
        $this->assertTrue($telegramDocument->isTelegram());
        $this->assertEquals('Surat Masuk', $incomingDocument->getTypeLabel());
        $this->assertEquals('Biasa', $draftDocument->getClassificationLabel());
        $this->assertEquals('Draft', $draftDocument->getStatusLabel());
        $this->assertEquals('Normal', $draftDocument->getPriorityLabel());
    }

    protected function createUserWithRole(string $roleName): User
    {
        $user = User::factory()->create();
        $role = Role::where('name', $roleName)->firstOrFail();
        $user->roles()->attach($role->id);

        return $user;
    }
}
