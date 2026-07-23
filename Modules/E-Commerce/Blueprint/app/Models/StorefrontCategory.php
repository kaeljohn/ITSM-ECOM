<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;

class StorefrontCategory extends Model
{
    protected $connection = 'ecommerce';
    protected $table = 'storefront_categories';
    protected $guarded = [];

    public function parent()
    {
        return $this->belongsTo(StorefrontCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(StorefrontCategory::class, 'parent_id');
    }
    
    public function listings()
    {
        return $this->hasMany(StorefrontListing::class, 'storefront_category_id');
    }
}