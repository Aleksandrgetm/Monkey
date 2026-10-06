<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'document_id' => $this->document_id, 'message' => $this->message, 'kind' => $this->kind, 'status' => $this->status, 'notification_date' => $this->notification_date?->toISOString(), 'document' => $this->whenLoaded('document'), 'created_at' => $this->created_at?->toISOString()];
    }
}
