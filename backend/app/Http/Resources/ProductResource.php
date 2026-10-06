<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'category_id' => $this->category_id, 'category' => new CategoryResource($this->whenLoaded('category')), 'merchant' => $this->merchant, 'amount' => $this->amount, 'purchase_date' => $this->purchase_date?->format('Y-m-d'), 'warranty_end_date' => $this->warranty_end_date?->format('Y-m-d'), 'note' => $this->note, 'documents_count' => (int) ($this->documents_count ?? 0), 'documents' => DocumentResource::collection($this->whenLoaded('documents')), 'created_at' => $this->created_at?->toISOString()];
    }
}
