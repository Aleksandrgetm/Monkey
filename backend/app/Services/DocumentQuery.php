<?php

namespace App\Services;

use App\Models\Document;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class DocumentQuery
{
    public function __construct(private WarrantyService $warranty) {}

    public function build(User $user, array $filters, bool $admin = false): Builder
    {
        $query = Document::query()->metadata()->with(['category', 'product']);
        if ($admin) {
            $query->with('owner');
        } else {
            $query->where('user_id', $user->id);
        }
        foreach (['category_id', 'product_id', 'merchant', 'kind'] as $field) {
            if (isset($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        if ($admin && isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', $search)->orWhere('file_name', 'like', $search)->orWhere('merchant', 'like', $search)->orWhere('note', 'like', $search);
            });
        }
        if (isset($filters['date_from'])) {
            $query->where('purchase_date', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('purchase_date', '<=', $filters['date_to']);
        }
        if (! empty($filters['warranty_status'])) {
            if ($admin) {
                $defaultDays = (int) SystemSetting::getValue('reminder_days', 30);
                $query->where(function (Builder $query) use ($filters, $defaultDays): void {
                    $daysGroups = User::query()->select('reminder_days')->distinct()->pluck('reminder_days');
                    foreach ($daysGroups as $days) {
                        $query->orWhere(function (Builder $query) use ($filters, $days, $defaultDays): void {
                            $query->whereHas('owner', fn (Builder $owner): Builder => $days === null ? $owner->whereNull('reminder_days') : $owner->where('reminder_days', $days));
                            $this->warranty->filter($query, $filters['warranty_status'], $days ?? $defaultDays);
                        });
                    }
                });
            } else {
                $this->warranty->filter($query, $filters['warranty_status'], $user->reminderDays());
            }
        }

        return $query->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc')->orderBy('id', 'desc');
    }
}
