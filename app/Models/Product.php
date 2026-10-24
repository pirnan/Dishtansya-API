<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'name',
        'available_stock',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'available_stock' => 'integer',
        ];
    }

    /**
     * Check if product has enough stock.
     */
    public function hasSufficientStock(int $quantity): bool
    {
        return $this->available_stock >= $quantity;
    }

    /**
     * Relationship: A product can be part of many orders.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
