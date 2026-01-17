<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductPolicy
{
    /**
     * Determine whether the user can update the product (Edit Harga/Stok).
     */
    public function update(User $user, Product $product): bool
    {
        // 1. Cek Permission Global dulu (via Spatie)
        // Apakah user punya hak akses "price.edit" ATAU "inventory.manage"?
        if (!$user->hasAnyPermission(['price.edit', 'inventory.manage'])) {
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
        if (!$user->hasPermissionTo('inventory.manage')) {
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
