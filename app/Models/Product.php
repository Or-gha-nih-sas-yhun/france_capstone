<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'sku',
        'name',
        'unit',
        'category',
        'price',
        'bulk_price',
        'bulk_min_qty',
        'quantity',
    ];

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * A product is sold at wholesale only once both a bulk price and the
     * quantity that unlocks it have been configured.
     */
    public function hasBulkPricing(): bool
    {
        return $this->bulk_price !== null
            && $this->bulk_price > 0
            && $this->bulk_min_qty !== null
            && $this->bulk_min_qty > 0;
    }

    /**
     * Unit price for the given quantity: bulk once the threshold is reached,
     * retail otherwise.
     */
    public function priceForQty(int $qty): float
    {
        if ($this->hasBulkPricing() && $qty >= $this->bulk_min_qty) {
            return (float) $this->bulk_price;
        }

        return (float) $this->price;
    }

    /**
     * Whether the given quantity is charged at the bulk rate.
     */
    public function isBulkQty(int $qty): bool
    {
        return $this->hasBulkPricing() && $qty >= $this->bulk_min_qty;
    }
}
