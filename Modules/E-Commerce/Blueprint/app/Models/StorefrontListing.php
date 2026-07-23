<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;

class StorefrontListing extends Model
{
    protected $connection = 'ecommerce';
    protected $table = 'storefront_listings';
    protected $guarded = [];

    public function category()
    {
        return $this->belongsTo(StorefrontCategory::class, 'storefront_category_id');
    }

    public function inventoryItem()
    {
        return $this->belongsTo(\Modules\Inventory\Models\Item::class, 'inventory_item_id');
    }
    
    public function getPriceAttribute()
    {
        return $this->override_price ?? $this->inventoryItem?->unit_cost ?? 0;
    }
    
    public function getNameAttribute($value)
    {
        return $value ?? $this->inventoryItem?->name ?? 'Unknown Item';
    }
}