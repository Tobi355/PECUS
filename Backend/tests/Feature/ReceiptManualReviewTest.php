<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Receipt;
use App\Models\Source;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReceiptManualReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_product_categories_without_demo_financial_records(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Ana',
            'surname' => 'Prueba',
            'email' => 'ana@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated();
        $this->assertDatabaseCount('categories', 8);
        $this->assertDatabaseCount('sources', 0);
        $this->assertDatabaseCount('movements', 0);
        $this->assertDatabaseCount('receipts', 0);
    }

    public function test_upload_saves_receipt_without_processing_or_creating_financial_data(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['surname' => 'Prueba']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/receipts', [
            'file' => UploadedFile::fake()->create('ticket.pdf', 100, 'application/pdf'),
        ]);

        $response->assertCreated()->assertJsonPath('status', 'RECEIVED')->assertJsonPath('extracted_data', null);
        $this->assertDatabaseCount('receipts', 1);
        $this->assertDatabaseCount('movements', 0);
        $this->assertNull(Receipt::first()->processed_at);
        $this->assertDatabaseHas('receipts', ['file_name' => 'ticket.pdf', 'status' => 'RECEIVED']);
    }

    public function test_receipt_file_is_only_available_to_its_owner(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create(['surname' => 'Titular']);
        $upload = $this->actingAs($owner, 'sanctum')->postJson('/api/receipts', [
            'file' => UploadedFile::fake()->create('private.pdf', 100, 'application/pdf'),
        ]);
        $receiptId = $upload->assertCreated()->json('id');

        $this->getJson("/api/receipts/{$receiptId}/file")->assertOk();

        $otherUser = User::factory()->create(['surname' => 'Otra']);
        $this->actingAs($otherUser, 'sanctum')->getJson("/api/receipts/{$receiptId}/file")->assertNotFound();
    }

    public function test_manual_review_is_required_and_confirmation_creates_only_one_movement(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['surname' => 'Prueba']);
        $this->actingAs($user, 'sanctum');
        $upload = $this->postJson('/api/receipts', ['file' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf')]);
        $receiptId = $upload->assertCreated()->json('id');

        $this->postJson("/api/receipts/{$receiptId}/confirm")->assertUnprocessable();
        $this->assertDatabaseCount('movements', 0);

        $source = Source::create(['user_id' => $user->id, 'name' => 'Cuenta personal', 'type' => 'WALLET']);
        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Alimentación',
            'type' => 'EXPENSE',
            'is_default' => true,
        ]);

        $this->putJson("/api/receipts/{$receiptId}", [
            'amount' => 1250.50,
            'date' => '2026-10-18',
            'merchant' => 'Comercio ingresado por usuario',
            'operation_type' => 'EXPENSE',
            'currency' => 'ARS',
            'source_id' => $source->id,
            'category_id' => $category->id,
        ])->assertOk()->assertJsonPath('status', 'REQUIRES_REVIEW');

        $confirmation = $this->postJson("/api/receipts/{$receiptId}/confirm");
        $confirmation->assertCreated()->assertJsonPath('receipt.status', 'CONFIRMED');
        $this->assertDatabaseCount('movements', 1);
        $this->assertDatabaseHas('movements', [
            'user_id' => $user->id,
            'source_id' => $source->id,
            'category_id' => $category->id,
            'amount' => 1250.50,
            'origin' => 'RECEIPT',
        ]);

        $this->postJson("/api/receipts/{$receiptId}/confirm")->assertUnprocessable();
        $this->assertDatabaseCount('movements', 1);
    }
}
