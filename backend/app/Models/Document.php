<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    use HasFactory;

    public const METADATA = ['id', 'user_id', 'category_id', 'product_id', 'name', 'file_name', 'file_type', 'file_size', 'kind', 'merchant', 'amount', 'purchase_date', 'warranty_end_date', 'note', 'created_at', 'updated_at'];

    protected $fillable = ['user_id', 'category_id', 'product_id', 'name', 'file_name', 'file_type', 'file_size', 'file_content', 'kind', 'merchant', 'amount', 'purchase_date', 'warranty_end_date', 'note'];

    protected $hidden = ['file_content'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'purchase_date' => 'date:Y-m-d', 'warranty_end_date' => 'date:Y-m-d'];
    }

    public function scopeMetadata(Builder $query): Builder
    {
        return $query->select(array_map(fn (string $column): string => 'documents.'.$column, self::METADATA));
    }

    public function resolveRouteBindingQuery($query, $value, $field = null): Builder
    {
        return parent::resolveRouteBindingQuery($query, $value, $field)->metadata();
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
}
