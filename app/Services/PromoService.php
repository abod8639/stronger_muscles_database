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
        return $this->promoRepository->create($data);
    }

    public function updatePromo(string $id, array $data): Promo
    {
        $promo = $this->promoRepository->findOrFail($id);

        $this->promoRepository->update($promo, $data);

        return $promo->fresh();
    }

    public function deletePromo(string $id): bool
    {
        $promo = $this->promoRepository->findOrFail($id);

        return $this->promoRepository->delete($promo);
    }
}
