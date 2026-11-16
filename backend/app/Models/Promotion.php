<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Promotion extends Model
{
    protected $fillable = ['product_id', 'discount_percentage', 'starts_at', 'ends_at'];

    protected function casts(): array
    {
        return [
            'discount_percentage' => 'decimal:2',
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isActive(): bool
    {
        $today = now()->toDateString();

        return $this->starts_at->toDateString() <= $today
            && $this->ends_at->toDateString() >= $today;
    }
}
