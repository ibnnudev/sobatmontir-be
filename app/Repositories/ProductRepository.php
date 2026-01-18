<?php

namespace App\Repositories;

use App\Models\Product;

class ProductRepository
{
    public function getByWorkshopId($workshopId, $filters = [], $limit = 10)
    {
        $query = Product::where('workshop_id', $workshopId);
        if (! empty($filters['search'])) {
            $query->where('name', 'like', "%{$filters['search']}%");
        }
        if (! empty($filters['low_stock'])) {
            $query->lowStock();
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        $query->latest();

        return $query->paginate($limit);
    }

    public function create(array $data)
    {
        return Product::create($data);
    }

    public function lockForUpdate($productId)
    {
        return Product::lockForUpdate()->findOrFail($productId);
    }

    public function updateStock(Product $product, int $realQty)
    {
        $product->update(['stock' => $realQty]);

        return $product;
    }
}
