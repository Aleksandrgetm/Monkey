<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Document;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_and_regular_users_cannot_access_any_admin_api(): void
    {
        $routes = ['/api/admin/users', '/api/admin/documents', '/api/admin/stats', '/api/admin/report', '/api/admin/settings', '/api/admin/health'];
        foreach ($routes as $route) {
            $this->getJson($route)->assertUnauthorized();
        }
        $user = User::factory()->create();
        $target = User::factory()->create();
        $document = Document::factory()->create();
        $this->actingAs($user);
        foreach ($routes as $route) {
            $this->getJson($route)->assertForbidden();
        }
        $this->patchJson('/api/admin/users/'.$target->id, ['role' => 1])->assertForbidden();
        $this->deleteJson('/api/admin/users/'.$target->id, ['confirmed' => true])->assertForbidden();
        $this->patchJson('/api/admin/documents/'.$document->id, ['name' => 'Changed'])->assertForbidden();
        $this->deleteJson('/api/admin/documents/'.$document->id, ['confirmed' => true])->assertForbidden();
        $this->getJson('/api/admin/documents/'.$document->id.'/file')->assertForbidden();
        $this->getJson('/api/admin/documents/'.$document->id.'/download')->assertForbidden();
        $this->patchJson('/api/admin/settings', ['registration_enabled' => false])->assertForbidden();
    }

    public function test_admin_can_search_filter_sort_and_manage_users(): void
    {
        $admin = User::factory()->create(['role' => 1]);
        $user = User::factory()->create(['name' => 'Alice123', 'email' => 'alice@example.test']);
        User::factory()->create(['name' => 'Bob123']);
        $this->actingAs($admin)->getJson('/api/admin/users?search=Alice&role=0&status=1&sort=email&direction=desc')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $user->id)->assertJsonMissingPath('data.0.password');
        $this->patchJson('/api/admin/users/'.$user->id, ['role' => 1, 'status' => 0])->assertOk()->assertJsonPath('role', 1)->assertJsonPath('status', 0);
        $this->deleteJson('/api/admin/users/'.$user->id)->assertUnprocessable();
        $this->deleteJson('/api/admin/users/'.$user->id, ['confirmed' => true])->assertNoContent();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_last_active_administrator_cannot_be_blocked_demoted_or_deleted_including_self_profile(): void
    {
        $admin = User::factory()->create(['role' => 1, 'password' => 'safePassword']);
        User::factory()->create(['role' => 1, 'status' => 0]);
        $this->actingAs($admin)->patchJson('/api/admin/users/'.$admin->id, ['role' => 0])->assertUnprocessable();
        $this->patchJson('/api/admin/users/'.$admin->id, ['status' => 0])->assertUnprocessable();
        $this->deleteJson('/api/admin/users/'.$admin->id, ['confirmed' => true])->assertUnprocessable();
        $this->deleteJson('/api/profile', ['confirmed' => true, 'current_password' => 'safePassword'])->assertUnprocessable();
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 1, 'status' => 1]);
    }

    public function test_admin_document_moderation_keeps_associations_with_the_documents_owner(): void
    {
        $admin = User::factory()->create(['role' => 1]);
        $owner = User::factory()->create();
        $document = Document::factory()->for($owner)->create();
        $ownerCategory = Category::factory()->for($owner)->create();
        $adminCategory = Category::factory()->for($admin)->create();
        $this->actingAs($admin)->getJson('/api/admin/documents?user_id='.$owner->id)->assertOk()->assertJsonPath('data.0.id', $document->id);
        $this->getJson('/api/admin/documents/'.$document->id)->assertOk()->assertJsonMissingPath('file_content');
        $this->get('/api/admin/documents/'.$document->id.'/file')->assertOk()->assertStreamedContent($document->file_content);
        $this->get('/api/admin/documents/'.$document->id.'/download')->assertOk()->assertDownload($document->file_name)->assertStreamedContent($document->file_content);
        $this->patchJson('/api/admin/documents/'.$document->id, ['category_id' => $adminCategory->id])->assertUnprocessable()->assertJsonValidationErrors('category_id');
        $this->patchJson('/api/admin/documents/'.$document->id, ['category_id' => $ownerCategory->id, 'name' => 'Corrected'])->assertOk()->assertJsonPath('name', 'Corrected');
        $this->deleteJson('/api/admin/documents/'.$document->id, ['confirmed' => true])->assertNoContent();
    }

    public function test_admin_can_delete_their_own_account_when_another_active_admin_remains(): void
    {
        $admin = User::factory()->create(['role' => 1]);
        User::factory()->create(['role' => 1]);
        $this->actingAs($admin)->deleteJson('/api/admin/users/'.$admin->id, ['confirmed' => true])->assertNoContent();
        $this->assertDatabaseMissing('users', ['id' => $admin->id]);
        $this->assertGuest();
        $this->getJson('/api/documents')->assertUnauthorized();
    }

    public function test_admin_warranty_filter_uses_each_owners_lead_time_and_matches_the_displayed_status(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 6)->startOfDay());
        $admin = User::factory()->create(['role' => 1, 'reminder_days' => 30]);
        $shortLead = User::factory()->create(['reminder_days' => 1]);
        $longLead = User::factory()->create(['reminder_days' => 14]);
        $active = Document::factory()->for($shortLead)->create(['warranty_end_date' => '2026-10-13']);
        $expiring = Document::factory()->for($longLead)->create(['warranty_end_date' => '2026-10-13']);
        $this->actingAs($admin)->getJson('/api/admin/documents?warranty_status=expiring')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $expiring->id)->assertJsonPath('data.0.warranty_status', 'expiring');
        $this->getJson('/api/admin/documents?warranty_status=active')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $active->id)->assertJsonPath('data.0.warranty_status', 'active');
    }

    public function test_system_statistics_settings_registration_switch_and_health_are_backed_by_data(): void
    {
        $admin = User::factory()->create(['role' => 1]);
        $blocked = User::factory()->create(['status' => 0]);
        Document::factory()->for($blocked)->create();
        $this->actingAs($admin)->getJson('/api/admin/stats')->assertOk()->assertJsonPath('users', 2)->assertJsonPath('blocked_users', 1)->assertJsonPath('documents', 1);
        $this->getJson('/api/admin/health')->assertOk()->assertJsonStructure(['database', 'scheduler_last_run', 'mail_mailer', 'last_backup_at']);
        $this->patchJson('/api/admin/settings', ['registration_enabled' => false, 'reminder_days' => 14])->assertOk()->assertJsonPath('registration_enabled', false);
        $this->assertFalse(SystemSetting::getValue('registration_enabled'));
        $this->get('/api/admin/report')->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->postJson('/api/auth/logout')->assertNoContent();
        $this->postJson('/api/auth/register', ['name' => 'NewUser', 'email' => 'new@example.test', 'password' => 'secret', 'password_confirmation' => 'secret'])->assertForbidden();
    }
}
