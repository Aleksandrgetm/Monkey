<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'document_id', 'message', 'kind', 'status', 'notification_date', 'deduplication_key', 'in_app', 'email_sent_at'];

    protected $hidden = ['deduplication_key'];

    protected function casts(): array
    {
        return ['notification_date' => 'datetime', 'email_sent_at' => 'datetime', 'status' => 'integer', 'in_app' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class)->select(['id', 'name', 'file_name']);
    }
}
