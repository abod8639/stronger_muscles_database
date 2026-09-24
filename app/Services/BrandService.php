<?php

namespace App\Services;

use App\Models\Brand;
use App\Repositories\BrandRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BrandService
{
    public function __construct(
        protected BrandRepository $brandRepository
    ) {}

    public function getActiveBrands(): Collection
    {
        return Cache::remember('brands:list', now()->addHours(6), function () {
            return $this->brandRepository->getActiveBrands();
        });
    }

    public function getAllBrands(): Collection
    {
        return $this->brandRepository->getAllBrands();
    }

    public function getBrand(string $id): Brand
    {
        return $this->brandRepository->findOrFail($id);
    }

    public function createBrand(array $data): Brand
    {
        if (empty($data['slug'])) {
            $base = $data['name']['en'] ?? $data['name']['ar'] ?? 'brand';
            $data['slug'] = Str::slug($base).'-'.Str::random(5);
        }

        $brand = $this->brandRepository->create($data);

        $this->clearCaches();

        return $brand;
    }

    public function updateBrand(string $id, array $data): Brand
    {
        $brand = $this->brandRepository->findOrFail($id);

        $this->brandRepository->update($brand, $data);

        $this->clearCaches();

        return $brand->fresh();
    }

    public function deleteBrand(string $id): bool
    {
        $brand = $this->brandRepository->findOrFail($id);

        if ($brand->products()->count() > 0) {
            throw new \DomainException('Cannot delete brand with associated products');
        }

        $deleted = $this->brandRepository->delete($brand);

        $this->clearCaches();

        return $deleted;
    }

    public function clearCaches(): void
    {
        Cache::forget('brands:list');
    }
}
