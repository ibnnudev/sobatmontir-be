<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Determine whether the user can create products.
     */
    public function create(User $user): bool
    {
        // Cek apakah user punya permission untuk membuat produk
        if (! $user->hasPermissionTo('manage_products')) {
            return false;
        }

        // User boleh membuat produk hanya jika dia memiliki bengkel
        return $user->ownedWorkshops()->exists();
    }

    /**
     * Determine whether the user can update the product (Edit Harga/Stok).
     */
    public function update(User $user, Product $product): bool
    {
        // 1. Cek Permission Global dulu (via Spatie)
        // Apakah user punya hak akses "price.edit" ATAU "manage_products"?
        if (! $user->hasAnyPermission(['price.edit', 'manage_products'])) {
            return false;
        }

        // 2. Cek Kepemilikan (Ownership)
        // Pastikan User ID sama dengan Owner ID dari Workshop produk tersebut
        // Kita load relasi workshop untuk cek owner_id
        return $user->id === $product->workshop->owner_id;
    }

    /**
     * Determine whether the user can delete the product.
     */
    public function delete(User $user, Product $product): bool
    {
        // Sama logika-nya, hanya owner bengkel yang boleh hapus produk
        if (! $user->hasPermissionTo('manage_products')) {
            return false;
        }

        return $user->id === $product->workshop->owner_id;
    }

    // Opsional: View
    public function view(User $user, Product $product): bool
    {
        // Siapapun boleh lihat produk (untuk search), atau batasi sesuai kebutuhan
        return true;
    }
}
