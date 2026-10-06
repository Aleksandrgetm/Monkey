<?php

namespace Tests\Feature;

use App\Mail\WarrantyReminder;
use App\Models\Document;
use App\Models\Notification;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\WarrantyService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WarrantyNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_warranty_boundaries_use_riga_calendar_dates_including_today_and_lead_day(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-05 22:30:00', 'UTC'));
        $service = app(WarrantyService::class);
        $this->assertSame('2026-10-06', $service->today()->toDateString());
        $this->assertNull($service->status(null));
        $this->assertSame('expired', $service->status('2026-10-05', 30));
        $this->assertSame('expiring', $service->status('2026-10-06', 30));
        $this->assertSame('expiring', $service->status('2026-11-05', 30));
        $this->assertSame('active', $service->status('2026-11-06', 30));
        $this->assertSame('active', $service->status('2026-10-07', 0));
    }

    public function test_reminders_are_deduplicated_but_new_expiration_dates_and_statuses_generate_new_events(): void
    {
        Mail::fake();
        $this->travelTo(CarbonImmutable::parse('2026-10-06 12:00', 'Europe/Riga'));
        $user = User::factory()->create(['email_notifications' => true, 'in_app_notifications' => true, 'reminder_days' => 30]);
        $document = Document::factory()->for($user)->create(['warranty_end_date' => '2026-10-10']);
        $this->artisan('scan:send-reminders')->assertSuccessful();
        $this->artisan('scan:send-reminders')->assertSuccessful();
        $this->assertDatabaseCount('notifications', 1);
        Mail::assertSent(WarrantyReminder::class, 1);
        $this->assertNotNull(SystemSetting::getValue('scheduler_last_run'));
        $document->update(['warranty_end_date' => '2026-10-12']);
        $this->artisan('scan:send-reminders')->assertSuccessful();
        $this->assertDatabaseCount('notifications', 2);
        $this->travelTo(CarbonImmutable::parse('2026-10-13 12:00', 'Europe/Riga'));
        $this->artisan('scan:send-reminders')->assertSuccessful();
        $this->assertDatabaseCount('notifications', 3);
        Mail::assertSent(WarrantyReminder::class, 3);
    }

    public function test_reminder_preferences_and_blocked_status_are_respected(): void
    {
        Mail::fake();
        $this->travelTo(CarbonImmutable::parse('2026-10-06 12:00', 'Europe/Riga'));
        $disabled = User::factory()->create(['email_notifications' => false, 'in_app_notifications' => false]);
        $blocked = User::factory()->create(['status' => 0]);
        $emailOnly = User::factory()->create(['email_notifications' => true, 'in_app_notifications' => false, 'reminder_days' => 30]);
        $inAppOnly = User::factory()->create(['email_notifications' => false, 'in_app_notifications' => true, 'reminder_days' => 30]);
        $shortLead = User::factory()->create(['email_notifications' => true, 'in_app_notifications' => true, 'reminder_days' => 1]);
        foreach ([$disabled, $blocked, $emailOnly, $inAppOnly, $shortLead] as $user) {
            Document::factory()->for($user)->create(['warranty_end_date' => '2026-10-10']);
        }
        $this->artisan('scan:send-reminders')->assertSuccessful();
        $this->assertDatabaseMissing('notifications', ['user_id' => $disabled->id]);
        $this->assertDatabaseMissing('notifications', ['user_id' => $blocked->id]);
        $this->assertDatabaseMissing('notifications', ['user_id' => $shortLead->id]);
        $this->assertDatabaseHas('notifications', ['user_id' => $inAppOnly->id, 'in_app' => true]);
        Mail::assertSent(WarrantyReminder::class, 1);
        Mail::assertSent(WarrantyReminder::class, fn (WarrantyReminder $mail): bool => $mail->hasTo($emailOnly->email));
        $this->actingAs($emailOnly)->getJson('/api/notifications')->assertOk()->assertJsonPath('total', 0);
    }

    public function test_notification_list_read_state_and_bulk_update_are_owner_scoped(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $mine = Notification::factory()->create(['user_id' => $user->id, 'status' => 0, 'in_app' => true, 'kind' => 'warranty']);
        $theirs = Notification::factory()->create(['user_id' => $other->id, 'status' => 0, 'in_app' => true]);
        $this->actingAs($user)->getJson('/api/notifications?status=0&kind=warranty')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $mine->id);
        $this->patchJson('/api/notifications/'.$theirs->id, ['status' => 1])->assertNotFound();
        $this->patchJson('/api/notifications/'.$mine->id, ['status' => 1])->assertSuccessful();
        $this->assertDatabaseHas('notifications', ['id' => $mine->id, 'status' => 1]);
        Notification::factory()->create(['user_id' => $user->id, 'status' => 0, 'in_app' => true]);
        $this->postJson('/api/notifications/read-all')->assertSuccessful();
        $this->assertSame(0, Notification::where('user_id', $user->id)->where('status', 0)->count());
        $this->assertDatabaseHas('notifications', ['id' => $theirs->id, 'status' => 0]);
    }
}
