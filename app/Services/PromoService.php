<?php

namespace App\Services;

use App\Models\Promo;
use App\Repositories\PromoRepository;
use Illuminate\Database\Eloquent\Collection;

class PromoService
{
    public function __construct(
        protected PromoRepository $promoRepository
    ) {}

    public function getActivePromosForCustomer(string $lang = 'ar'): array
    {
        $lang = str_contains($lang, 'en') ? 'en' : 'ar';

        return \Illuminate\Support\Facades\Cache::remember("promos:active:{$lang}", now()->addHours(2), function () use ($lang) {
            return $this->promoRepository->getActivePromos()->map(function ($promo) use ($lang) {
                $promoArray = $promo->toArray();

                $getField = function ($field) use ($promo, $lang) {
                    if (is_array($promo->$field)) {
                        return $promo->$field[$lang] ?? $promo->$field['ar'] ?? $promo->$field['en'] ?? null;
                    }

                    return $promo->$field;
                };

                $promoArray['title'] = $getField('title');
                $promoArray['subtitle'] = $getField('subtitle');
                $promoArray['button_text'] = $getField('button_text') ?: ($lang === 'en' ? 'Shop Now' : 'عرض الآن');

                return $promoArray;
            })->values()->toArray();
        });
    }

    public function getAllPromos(): Collection
    {
        return $this->promoRepository->getAllPromos();
    }

    public function getPromo(string $id): Promo
    {
        return $this->promoRepository->findOrFail($id);
    }

    public function createPromo(array $data): Promo
    {
        $promo = $this->promoRepository->create($data);
        $this->clearCaches();

        return $promo;
    }

    public function updatePromo(string $id, array $data): Promo
    {
        $promo = $this->promoRepository->findOrFail($id);

        $this->promoRepository->update($promo, $data);
        $this->clearCaches();

        return $promo->fresh();
    }

    public function deletePromo(string $id): bool
    {
        $promo = $this->promoRepository->findOrFail($id);
        $deleted = $this->promoRepository->delete($promo);
        $this->clearCaches();

        return $deleted;
    }

    /**
     * Clear promo caches.
     */
    public function clearCaches(): void
    {
        \Illuminate\Support\Facades\Cache::forget('promos:active:ar');
        \Illuminate\Support\Facades\Cache::forget('promos:active:en');
    }
}
