<?php

namespace App\Http\Resources;

use App\Services\WarrantyService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $days = $this->relationLoaded('owner') ? $this->owner?->reminderDays() : ($request->user()?->id === $this->user_id ? $request->user()->reminderDays() : null);

        return [
            'id' => $this->id, 'user_id' => $this->user_id, 'category_id' => $this->category_id, 'product_id' => $this->product_id,
            'category' => new CategoryResource($this->whenLoaded('category')), 'product' => new ProductResource($this->whenLoaded('product')),
            'owner' => $this->when($request->is('api/admin/*') && $this->relationLoaded('owner'), fn (): UserResource => new UserResource($this->owner)),
            'name' => $this->name, 'file_name' => $this->file_name, 'file_type' => $this->file_type, 'file_size' => $this->file_size,
            'kind' => $this->kind, 'merchant' => $this->merchant, 'amount' => $this->amount, 'purchase_date' => $this->purchase_date?->format('Y-m-d'),
            'warranty_end_date' => $this->warranty_end_date?->format('Y-m-d'), 'note' => $this->note,
            'warranty_status' => app(WarrantyService::class)->status($this->warranty_end_date, $days), 'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
