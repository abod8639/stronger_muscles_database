<?php

namespace App\Repositories;

use App\Models\Promo;
use Illuminate\Database\Eloquent\Collection;

class PromoRepository
{
    public function getActivePromos(): Collection
    {
        return Promo::where('is_active', true)->latest()->get();
    }

    public function getAllPromos(): Collection
    {
        return Promo::latest()->get();
    }

    public function findOrFail(string $id): Promo
    {
        return Promo::findOrFail($id);
    }

    public function create(array $data): Promo
    {
        return Promo::create($data);
    }

    public function update(Promo $promo, array $data): Promo
    {
        $promo->update($data);

        return $promo;
    }

    public function delete(Promo $promo): bool
    {
        return $promo->delete();
    }
}
