<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Document;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DocumentManagementTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private array $uploadPaths = [];

    protected function tearDown(): void
    {
        foreach ($this->uploadPaths as $path) {
            unlink($path);
        }
        parent::tearDown();
    }

    private function upload(string $name, string $contents): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'scan-save-upload-');
        file_put_contents($path, $contents);
        $this->uploadPaths[] = $path;

        return new UploadedFile($path, $name, null, null, true);
    }

    private function pdf(string $name = 'receipt.pdf'): UploadedFile
    {
        return $this->upload($name, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n");
    }

    public function test_full_document_lifecycle_preserves_private_binary_file_and_rounded_amount(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $file = $this->pdf();
        $contents = file_get_contents($file->getPathname());
        $response = $this->actingAs($user)->postJson('/api/documents', [
            'file' => $file, 'category_id' => $category->id, 'name' => 'Laptop', 'amount' => '99999.99',
            'purchase_date' => '2026-01-01', 'warranty_end_date' => '2028-01-01', 'kind' => 'receipt',
        ])->assertCreated()->assertJsonPath('amount', '99999.99')->assertJsonPath('file_type', 'application/pdf')->assertJsonMissingPath('file_content');
        $id = $response->json('id');
        $this->getJson('/api/documents/'.$id)->assertOk()->assertJsonPath('name', 'Laptop')->assertJsonMissingPath('file_content');
        $this->get('/api/documents/'.$id.'/download')->assertOk()->assertDownload('receipt.pdf')->assertStreamedContent($contents);
        $this->get('/api/documents/'.$id.'/file')->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertStreamedContent($contents);
        $this->patchJson('/api/documents/'.$id, ['name' => 'Work laptop', 'note' => 'Keep original'])->assertOk()->assertJsonPath('name', 'Work laptop');
        $this->postJson('/api/documents/'.$id, ['_method' => 'PATCH', 'file' => $this->pdf('replacement.pdf')])->assertOk()->assertJsonPath('file_name', 'replacement.pdf');
        $this->deleteJson('/api/documents/'.$id)->assertUnprocessable();
        $this->deleteJson('/api/documents/'.$id, ['confirmed' => true])->assertNoContent();
        $this->assertDatabaseMissing('documents', ['id' => $id]);
        $this->get('/api/documents/'.$id.'/download')->assertNotFound();
    }

    public function test_successful_upload_creates_a_real_unread_system_notification_without_sending_mail(): void
    {
        Mail::fake();
        $user = User::factory()->create(['in_app_notifications' => true, 'email_notifications' => true]);
        $category = Category::factory()->for($user)->create();
        $this->actingAs($user)->postJson('/api/documents', ['category_id' => $category->id])->assertUnprocessable();
        $this->assertDatabaseCount('notifications', 0);
        $id = $this->postJson('/api/documents', ['category_id' => $category->id, 'file' => $this->pdf(), 'name' => 'Pirkuma čeks'])->assertCreated()->json('id');
        $this->assertDatabaseHas('notifications', ['user_id' => $user->id, 'document_id' => $id, 'kind' => 'system', 'status' => 0, 'in_app' => true]);
        $this->getJson('/api/notifications?kind=system&status=0')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.document.id', $id)->assertJsonPath('data.0.message', 'Dokuments “Pirkuma čeks” ir veiksmīgi pievienots.');
        $this->getJson('/api/dashboard')->assertOk()->assertJsonPath('unread', 1);
        Mail::assertNothingSent();
        $this->deleteJson('/api/documents/'.$id, ['confirmed' => true])->assertNoContent();
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_upload_respects_disabled_in_app_notifications(): void
    {
        Mail::fake();
        $user = User::factory()->create(['in_app_notifications' => false, 'email_notifications' => true]);
        $category = Category::factory()->for($user)->create();
        $this->actingAs($user)->postJson('/api/documents', ['category_id' => $category->id, 'file' => $this->pdf()])->assertCreated();
        $this->assertDatabaseCount('notifications', 0);
        Mail::assertNothingSent();
    }

    public function test_every_document_operation_is_scoped_to_the_owner_even_for_an_administrator(): void
    {
        $owner = User::factory()->create();
        $document = Document::factory()->for($owner)->create();
        foreach ([User::factory()->create(), User::factory()->create(['role' => 1])] as $visitor) {
            $this->actingAs($visitor);
            foreach (['', '/file', '/download'] as $suffix) {
                $this->getJson('/api/documents/'.$document->id.$suffix)->assertNotFound();
            }
            $this->patchJson('/api/documents/'.$document->id, ['name' => 'Changed'])->assertNotFound();
            $this->deleteJson('/api/documents/'.$document->id, ['confirmed' => true])->assertNotFound();
            $this->getJson('/api/documents')->assertOk()->assertJsonPath('total', 0);
        }
        $this->assertDatabaseHas('documents', ['id' => $document->id, 'name' => $document->name]);
    }

    public function test_file_validation_checks_actual_content_and_strict_ten_mebibyte_limit(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $this->actingAs($user);
        foreach ([
            $this->upload('malware.pdf', '<script>alert(1)</script>'),
            $this->upload('too-large.pdf', str_pad("%PDF-1.4\n", 10485760, 'x')),
            $this->upload('program.exe', "MZ\0program"),
        ] as $file) {
            $this->postJson('/api/documents', ['category_id' => $category->id, 'file' => $file])->assertUnprocessable()->assertJsonValidationErrors('file');
        }
        $this->postJson('/api/documents', ['category_id' => $category->id, 'file' => $this->upload('large.pdf', str_pad("%PDF-1.4\n", 10485759, 'x'))])->assertCreated();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l1sAAAAASUVORK5CYII=');
        $this->postJson('/api/documents', ['category_id' => $category->id, 'file' => $this->upload('pixel.png', $png)])->assertCreated()->assertJsonPath('file_type', 'image/png');
        $this->postJson('/api/documents', ['category_id' => $category->id])->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->postJson('/api/documents', ['category_id' => $category->id, 'file' => 'not-an-upload'])->assertUnprocessable()->assertJsonValidationErrors('file');
    }

    public function test_metadata_limits_dates_and_owner_associations_are_validated(): void
    {
        $user = User::factory()->create();
        $otherCategory = Category::factory()->create();
        $otherProduct = Product::factory()->create();
        $category = Category::factory()->for($user)->create();
        $this->actingAs($user)->postJson('/api/documents', [
            'file' => $this->pdf(), 'category_id' => $otherCategory->id, 'product_id' => $otherProduct->id,
            'name' => str_repeat('x', 256), 'amount' => '100000.00', 'note' => str_repeat('x', 301),
            'purchase_date' => '2026-02-30', 'warranty_end_date' => 'invalid', 'kind' => 'invalid',
        ])->assertUnprocessable()->assertJsonValidationErrors(['category_id', 'product_id', 'name', 'amount', 'note', 'purchase_date', 'warranty_end_date', 'kind']);
        $this->postJson('/api/documents', ['file' => $this->pdf(), 'category_id' => $category->id, 'amount' => '-0.01'])->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->postJson('/api/documents', ['file' => $this->pdf(), 'category_id' => $category->id, 'purchase_date' => '2026-01-02', 'warranty_end_date' => '2026-01-01'])->assertUnprocessable()->assertJsonValidationErrors('warranty_end_date');
    }

    public function test_search_filters_and_sort_work_together_and_never_include_another_owner(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 6)->startOfDay());
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $first = Document::factory()->for($user)->for($category)->create(['name' => 'Laptop Apple', 'merchant' => 'Store', 'kind' => 'receipt', 'amount' => '100', 'purchase_date' => '2026-01-10', 'warranty_end_date' => '2026-10-10']);
        $second = Document::factory()->for($user)->for($category)->create(['name' => 'Laptop Dell', 'merchant' => 'Store', 'kind' => 'receipt', 'amount' => '200', 'purchase_date' => '2026-01-15', 'warranty_end_date' => '2026-10-15']);
        Document::factory()->for($user)->for($category)->create(['name' => 'Laptop HP', 'kind' => 'other', 'warranty_end_date' => '2026-09-01']);
        Document::factory()->create(['name' => 'Laptop Private', 'merchant' => 'Store', 'kind' => 'receipt']);
        $query = http_build_query(['search' => 'Laptop', 'category_id' => $category->id, 'merchant' => 'Store', 'kind' => 'receipt', 'warranty_status' => 'expiring', 'date_from' => '2026-01-01', 'date_to' => '2026-01-31', 'sort' => 'amount', 'direction' => 'desc']);
        $this->actingAs($user)->getJson('/api/documents?'.$query)->assertOk()->assertJsonPath('total', 2)->assertJsonPath('data.0.id', $second->id)->assertJsonPath('data.1.id', $first->id)->assertJsonMissingPath('data.0.file_content');
        $this->getJson('/api/documents?search=unfindable')->assertOk()->assertJsonPath('total', 0);
        $this->getJson('/api/documents?sort=password&direction=sideways')->assertUnprocessable()->assertJsonValidationErrors(['sort', 'direction']);
    }

    public function test_category_crud_is_owned_and_referenced_categories_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $otherCategory = Category::factory()->create();
        $this->actingAs($user)->getJson('/api/categories')->assertOk()->assertJsonCount(0, 'data');
        $id = $this->postJson('/api/categories', ['name' => 'Electronics'])->assertCreated()->json('id');
        $this->patchJson('/api/categories/'.$id, ['name' => 'Devices'])->assertOk()->assertJsonPath('name', 'Devices');
        $this->patchJson('/api/categories/'.$otherCategory->id, ['name' => 'Stolen'])->assertNotFound();
        $this->deleteJson('/api/categories/'.$otherCategory->id, ['confirmed' => true])->assertNotFound();
        $document = Document::factory()->for($user)->create(['category_id' => $id]);
        $this->deleteJson('/api/categories/'.$id, ['confirmed' => true])->assertUnprocessable();
        $document->delete();
        $this->deleteJson('/api/categories/'.$id, ['confirmed' => true])->assertNoContent();
    }

    public function test_product_crud_and_linked_documents_are_owned(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $otherProduct = Product::factory()->create();
        $this->actingAs($user);
        $id = $this->postJson('/api/products', ['name' => 'Computer', 'category_id' => $category->id, 'amount' => '199.99'])->assertCreated()->json('id');
        $document = Document::factory()->for($user)->for($category)->create(['product_id' => $id]);
        $this->getJson('/api/products/'.$id)->assertOk()->assertJsonPath('documents.0.id', $document->id);
        $this->patchJson('/api/products/'.$id, ['name' => 'Work Computer'])->assertOk()->assertJsonPath('name', 'Work Computer');
        $this->getJson('/api/products?search=Work&sort=amount&direction=asc')->assertOk()->assertJsonPath('total', 1);
        $this->getJson('/api/products/'.$otherProduct->id)->assertNotFound();
        $this->patchJson('/api/products/'.$otherProduct->id, ['name' => 'Stolen'])->assertNotFound();
        $this->deleteJson('/api/products/'.$id, ['confirmed' => true])->assertNoContent();
        $this->assertDatabaseHas('documents', ['id' => $document->id, 'product_id' => null]);
    }
}
