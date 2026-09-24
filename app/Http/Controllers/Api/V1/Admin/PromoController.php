<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Promo\StorePromoRequest;
use App\Http\Requests\Admin\Promo\UpdatePromoRequest;
use App\Services\PromoService;

class PromoController extends Controller
{
    public function __construct(
        protected PromoService $promoService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $promos = $this->promoService->getAllPromos();

        return response()->json($promos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePromoRequest $request)
    {
        $validated = $request->validated();

        $promo = $this->promoService->createPromo($validated);

        return response()->json([
            'message' => 'تم إنشاء الإعلان بنجاح',
            'data' => $promo,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $promo = $this->promoService->getPromo($id);

        return response()->json($promo);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePromoRequest $request, string $id)
    {
        $validated = $request->validated();

        $promo = $this->promoService->updatePromo($id, $validated);

        return response()->json([
            'message' => 'تم تحديث الإعلان بنجاح',
            'data' => $promo,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->promoService->deletePromo($id);

        return response()->json([
            'message' => 'تم حذف الإعلان بنجاح',
        ]);
    }
}
