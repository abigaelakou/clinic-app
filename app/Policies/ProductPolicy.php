<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return count($user->readableStockDomains()) > 0;
    }

    public function view(User $user, Product $product): bool
    {
        return $user->canReadStockDomain($product->category->domain ?? '');
    }

    public function create(User $user, string $domain): bool
    {
        return $user->canWriteStockDomain($domain);
    }

    public function update(User $user, Product $product): bool
    {
        return $user->canWriteStockDomain($product->category->domain ?? '');
    }

    public function recordMovement(User $user, Product $product): bool
    {
        return $this->update($user, $product);
    }
}
