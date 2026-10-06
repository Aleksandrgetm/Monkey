<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'category_id', 'name', 'merchant', 'amount', 'purchase_date', 'warranty_end_date', 'note'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'purchase_date' => 'date:Y-m-d', 'warranty_end_date' => 'date:Y-m-d'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class)->metadata();
    }
}
