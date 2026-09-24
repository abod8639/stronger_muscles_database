<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository
{
    /**
     * Get active categories for public listing.
     */
    public function getActiveCategories(): Collection
    {
        return Category::active()
            ->ordered()
            ->forListView()
            ->withProductCount()
            ->get();
    }

    /**
     * Find active category by ID.
     */
    public function findActiveCategory(string $id): Category
    {
        return Category::active()
            ->withProductCount()
            ->findOrFail($id);
    }

    /**
     * Get categories for admin with optional tree filtering.
     */
    public function getAdminCategories(bool $treeOnly = false): Collection
    {
        $query = Category::query();

        if ($treeOnly) {
            $query->whereNull('parent_id');
        }

        return $query->get();
    }

    /**
     * Find category by ID.
     */
    public function findOrFail(string $id): Category
    {
        return Category::findOrFail($id);
    }

    /**
     * Create category.
     */
    public function create(array $data): Category
    {
        return Category::create($data);
    }

    /**
     * Update category.
     */
    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        return $category;
    }

    /**
     * Delete category.
     */
    public function delete(Category $category): bool
    {
        return $category->delete();
    }
}
