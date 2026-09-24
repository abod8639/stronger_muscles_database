<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class CategoryService
{
    public function __construct(
        protected CategoryRepository $categoryRepository
    ) {}

    /**
     * Get active categories for public API.
     */
    public function getActiveCategories(): Collection
    {
        return Cache::remember('categories:active:list', now()->addHours(2), function () {
            return $this->categoryRepository->getActiveCategories();
        });
    }

    /**
     * Get active category details for public API.
     */
    public function getActiveCategory(string $id): Category
    {
        return Cache::remember("category:{$id}", now()->addHours(2), function () use ($id) {
            return $this->categoryRepository->findActiveCategory($id);
        });
    }

    /**
     * Get categories for admin.
     */
    public function getAdminCategories(bool $treeOnly = false): Collection
    {
        return $this->categoryRepository->getAdminCategories($treeOnly);
    }

    /**
     * Get single category for admin.
     */
    public function getAdminCategory(string $id): Category
    {
        return $this->categoryRepository->findOrFail($id);
    }

    /**
     * Create category.
     */
    public function createCategory(array $data): Category
    {
        $category = $this->categoryRepository->create($data);

        $this->clearCaches($category->id);

        return $category;
    }

    /**
     * Update category.
     */
    public function updateCategory(string $id, array $data): Category
    {
        $category = $this->categoryRepository->findOrFail($id);

        $this->categoryRepository->update($category, $data);

        $this->clearCaches($id);

        return $category->fresh();
    }

    /**
     * Delete category.
     */
    public function deleteCategory(string $id): bool
    {
        $category = $this->categoryRepository->findOrFail($id);

        if ($category->products()->count() > 0) {
            throw new \DomainException('Cannot delete category with associated products');
        }

        $deleted = $this->categoryRepository->delete($category);

        $this->clearCaches($id);

        return $deleted;
    }

    /**
     * Clear category caches.
     */
    public function clearCaches(?string $id = null): void
    {
        Cache::forget('categories:active:list');
        Cache::forget('categories:tree');
        Cache::forget('categories_tree');
        Cache::forget('shop_categories_list');
        Cache::forget('categories_list');

        if ($id) {
            Cache::forget("category:{$id}");
        }
    }
}
