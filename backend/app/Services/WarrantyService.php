<?php

namespace App\Services;

use App\Models\SystemSetting;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;

class WarrantyService
{
    public function today(): CarbonImmutable
    {
        return CarbonImmutable::today('Europe/Riga');
    }

    public function status(DateTimeInterface|string|null $date, ?int $days = null): ?string
    {
        if ($date === null) {
            return null;
        }
        $end = CarbonImmutable::parse($date instanceof DateTimeInterface ? $date->format('Y-m-d') : $date, 'Europe/Riga')->startOfDay();
        $today = $this->today();
        if ($end->lt($today)) {
            return 'expired';
        }

        return $end->lte($today->addDays($days ?? (int) SystemSetting::getValue('reminder_days', 30))) ? 'expiring' : 'active';
    }

    public function filter(Builder $query, string $status, int $days): Builder
    {
        $today = $this->today()->toDateString();
        $threshold = $this->today()->addDays($days)->toDateString();

        return match ($status) {
            'expired' => $query->where('warranty_end_date', '<', $today),
            'expiring' => $query->whereBetween('warranty_end_date', [$today, $threshold]),
            'active' => $query->where('warranty_end_date', '>', $threshold),
            default => $query,
        };
    }
}
