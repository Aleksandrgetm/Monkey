<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'documents_count' => (int) ($this->documents_count ?? 0), 'products_count' => (int) ($this->products_count ?? 0)];
    }
}
