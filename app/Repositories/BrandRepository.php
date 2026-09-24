<?php

namespace App\Repositories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Collection;

class BrandRepository
{
    public function getActiveBrands(): Collection
    {
        return Brand::active()
            ->ordered()
            ->withCount('products')
            ->get();
    }

    public function getAllBrands(): Collection
    {
        return Brand::orderBy('created_at', 'desc')->get();
    }

    public function findOrFail(string $id): Brand
    {
        return Brand::findOrFail($id);
    }

    public function create(array $data): Brand
    {
        return Brand::create($data);
    }

    public function update(Brand $brand, array $data): Brand
    {
        $brand->update($data);

        return $brand;
    }

    public function delete(Brand $brand): bool
    {
        return $brand->delete();
    }
}
