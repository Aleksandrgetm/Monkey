<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Document;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OperationsTest extends TestCase
{
    private string $sandbox;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sandbox = sys_get_temp_dir().'/scan-save-test-'.bin2hex(random_bytes(8));
        File::makeDirectory($this->sandbox, 0700, true);
        File::makeDirectory($this->sandbox.'/storage/framework', 0700, true);
        touch($this->sandbox.'/application.sqlite');
        config(['database.connections.sqlite.database' => $this->sandbox.'/application.sqlite']);
        $this->app->useStoragePath($this->sandbox.'/storage');
        DB::purge('sqlite');
        Artisan::call('migrate', ['--force' => true]);
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        File::deleteDirectory($this->sandbox);
        parent::tearDown();
    }

    public function test_backup_contains_binary_documents_and_restore_recovers_them_in_a_temporary_database(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $binary = "%PDF-1.4\nBinary \0 \xFF document";
        $document = Document::factory()->for($user)->for($category)->create(['name' => 'Before snapshot', 'file_content' => $binary]);
        $service = app(BackupService::class);
        $backup = $service->create($this->sandbox.'/backups');
        $this->assertSame($backup['sha256'], hash_file('sha256', $backup['path'].'/database.sqlite'));
        $this->artisan('scan:restore', ['backup' => $backup['path'], '--verify' => true])->assertSuccessful();
        $document->update(['name' => 'After snapshot', 'file_content' => 'changed']);
        $this->artisan('scan:restore', ['backup' => $backup['path']])->assertFailed();
        $this->artisan('scan:restore', ['backup' => $backup['path'], '--force' => true])->assertFailed();
        $this->assertDatabaseHas('documents', ['id' => $document->id, 'name' => 'After snapshot']);
        Artisan::call('down');
        $this->artisan('scan:restore', ['backup' => $backup['path'], '--force' => true])->assertSuccessful();
        $this->assertDatabaseHas('documents', ['id' => $document->id, 'name' => 'Before snapshot']);
        $this->assertSame($binary, DB::table('documents')->where('id', $document->id)->value('file_content'));
        $this->assertCount(1, File::directories($this->sandbox.'/storage/app/private/backups'));
        Artisan::call('up');
    }

    public function test_corrupt_backup_is_rejected_without_changing_the_database(): void
    {
        $user = User::factory()->create(['name' => 'Preserved']);
        $backup = app(BackupService::class)->create($this->sandbox.'/backups');
        File::append($backup['path'].'/database.sqlite', 'corrupted');
        Artisan::call('down');
        $this->artisan('scan:restore', ['backup' => $backup['path'], '--force' => true])->assertFailed();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Preserved']);
        Artisan::call('up');
    }

    public function test_backup_command_creates_a_verifiable_private_snapshot(): void
    {
        $this->artisan('scan:backup', ['--directory' => $this->sandbox.'/backups'])->assertSuccessful();
        $paths = File::directories($this->sandbox.'/backups');
        $this->assertCount(1, $paths);
        $this->artisan('scan:restore', ['backup' => $paths[0], '--verify' => true])->assertSuccessful();
        $this->assertSame(0600, fileperms($paths[0].'/database.sqlite') & 0777);
    }

    public function test_create_admin_has_no_unattended_default_password(): void
    {
        $this->artisan('scan:create-admin', ['email' => 'admin@example.test', '--name' => 'Admin123', '--no-interaction' => true])->assertFailed();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_create_admin_accepts_only_a_secure_confirmed_interactive_password(): void
    {
        $this->artisan('scan:create-admin', ['email' => 'Admin@Example.test', '--name' => 'Admin123'])
            ->expectsQuestion('Password (at least 12 characters)', 'TemporaryTest123!')
            ->expectsQuestion('Confirm password', 'TemporaryTest123!')
            ->assertSuccessful();
        $admin = User::where('email', 'admin@example.test')->sole();
        $this->assertSame(1, $admin->role);
        $this->assertTrue(Hash::check('TemporaryTest123!', $admin->password));
    }
}
