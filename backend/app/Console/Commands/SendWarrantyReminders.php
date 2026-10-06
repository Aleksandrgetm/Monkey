<?php

namespace App\Console\Commands;

use App\Mail\WarrantyReminder;
use App\Models\Document;
use App\Models\Notification;
use App\Models\SystemSetting;
use App\Services\WarrantyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendWarrantyReminders extends Command
{
    protected $signature = 'scan:send-reminders';

    protected $description = 'Generate warranty expiry reminders and deliver enabled email notifications without duplicates';

    public function handle(WarrantyService $warranty): int
    {
        $lock = Cache::lock('scan:send-reminders', 3600);
        if (! $lock->get()) {
            $this->info('Another reminder run is already in progress.');

            return self::SUCCESS;
        }
        $created = 0;
        $sent = 0;
        $failed = 0;
        try {
            Document::metadata()->whereNotNull('warranty_end_date')->whereHas('owner', fn ($query) => $query->where('status', 1))->with('owner')->chunkById(100, function ($documents) use ($warranty, &$created, &$sent, &$failed): void {
                foreach ($documents as $document) {
                    $user = $document->owner;
                    if (! $user->email_notifications && ! $user->in_app_notifications) {
                        continue;
                    }
                    $status = $warranty->status($document->warranty_end_date, $user->reminderDays());
                    if (! in_array($status, ['expiring', 'expired'], true)) {
                        continue;
                    }
                    $end = $document->warranty_end_date->format('Y-m-d');
                    $title = $document->name ?: $document->file_name;
                    $displayDate = $document->warranty_end_date->format('d.m.Y');
                    $message = $status === 'expired' ? 'Dokumenta “'.$title.'” garantija beidzās '.$displayDate.'.' : 'Dokumenta “'.$title.'” garantija beidzas '.$displayDate.'.';
                    $notification = Notification::firstOrCreate(['deduplication_key' => $document->id.':'.$end.':'.$status], ['user_id' => $user->id, 'document_id' => $document->id, 'message' => $message, 'kind' => 'warranty', 'status' => 0, 'notification_date' => now(), 'in_app' => $user->in_app_notifications]);
                    if ($notification->wasRecentlyCreated) {
                        $created++;
                    }
                    if ($user->email_notifications && $notification->email_sent_at === null) {
                        try {
                            Mail::to($user->email)->send(new WarrantyReminder($document, $notification));
                            $notification->update(['email_sent_at' => now()]);
                            $sent++;
                        } catch (Throwable $exception) {
                            report($exception);
                            $failed++;
                        }
                    }
                }
            });
            SystemSetting::setValue('scheduler_last_run', now()->toISOString());
            $this->info("Created {$created} reminders; sent {$sent} emails; {$failed} delivery failures.");

            return $failed > 0 ? self::FAILURE : self::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}
