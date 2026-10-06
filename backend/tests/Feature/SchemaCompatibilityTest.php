<?php

namespace Tests\Feature;

use App\Http\Resources\NotificationResource;
use App\Models\Category;
use App\Models\Document;
use App\Models\Notification;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchemaCompatibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->artisan('migrate', ['--path' => [
            'database/migrations/0001_01_01_000000_create_users_table.php',
            'database/migrations/0001_01_01_000001_create_cache_table.php',
            'database/migrations/0001_01_01_000002_create_jobs_table.php',
            'database/migrations/2026_10_06_104750_create_scan_save_tables.php',
        ], '--force' => true])->assertSuccessful();
    }

    public function test_corrective_migration_preserves_existing_records_binary_content_and_relationships(): void
    {
        $user = User::factory()->create(['name' => 'Jānis123']);
        $category = Category::factory()->for($user)->create();
        $product = Product::factory()->for($user)->for($category)->create(['note' => 'Saglabāta produkta piezīme']);
        $binary = "%PDF-1.4\nBinary \0 \xFF document";
        $document = Document::factory()->for($user)->for($category)->for($product)->create(['note' => 'Saglabāta dokumenta piezīme', 'amount' => '99999.99', 'file_content' => $binary]);
        $notification = Notification::factory()->for($user)->for($document)->create(['message' => 'Saglabāts paziņojums', 'status' => 1]);
        DB::table('notifications')->where('id', $notification->id)->update(['notification_date' => '2026-10-06']);

        $this->artisan('migrate', ['--force' => true])->assertSuccessful();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Jānis123']);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'category_id' => $category->id, 'note' => 'Saglabāta produkta piezīme']);
        $this->assertDatabaseHas('documents', ['id' => $document->id, 'product_id' => $product->id, 'category_id' => $category->id, 'note' => 'Saglabāta dokumenta piezīme', 'amount' => '99999.99']);
        $this->assertSame($binary, DB::table('documents')->where('id', $document->id)->value('file_content'));
        $this->assertDatabaseHas('notifications', ['id' => $notification->id, 'document_id' => $document->id, 'status' => 1, 'message' => 'Saglabāts paziņojums']);
        $this->assertSame('2026-10-06', $notification->fresh()->notification_date->format('Y-m-d'));
        $this->assertSame('text', Schema::getColumnType('documents', 'note'));
        $this->assertSame('text', Schema::getColumnType('products', 'note'));
        $this->assertSame('text', Schema::getColumnType('notifications', 'message'));
        $this->assertSame('datetime', Schema::getColumnType('notifications', 'notification_date'));
        $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
    }

    public function test_notification_timestamps_keep_time_and_json_uses_iso_format(): void
    {
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        $this->travelTo(now()->setDate(2026, 10, 6)->setTime(13, 42, 17));
        $notification = Notification::factory()->create(['notification_date' => now(), 'message' => str_repeat('Paziņojums. ', 100)]);
        $stored = $notification->fresh();
        $this->assertSame('13:42:17', $stored->notification_date->format('H:i:s'));
        $this->assertSame(now()->toISOString(), (new NotificationResource($stored))->resolve()['notification_date']);
        $this->assertGreaterThan(500, mb_strlen($stored->message));
    }
}
