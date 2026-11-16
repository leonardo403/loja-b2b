<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentCondition extends Model
{
    protected $fillable = ['code', 'description', 'minimum_value', 'surcharge_percentage', 'is_term'];

    protected function casts(): array
    {
        return [
            'minimum_value' => 'decimal:2',
            'surcharge_percentage' => 'decimal:2',
            'is_term' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
