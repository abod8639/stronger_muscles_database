<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Promo\StorePromoRequest;
use App\Http\Requests\Admin\Promo\UpdatePromoRequest;
use App\Models\Promo;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $promos = Promo::latest()->get();
        return response()->json($promos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePromoRequest $request)
    {
        $validated = $request->validated();

        $promo = Promo::create($validated);

        return response()->json([
            'message' => 'تم إنشاء الإعلان بنجاح',
            'data' => $promo
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $promo = Promo::findOrFail($id);
        return response()->json($promo);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePromoRequest $request, string $id)
    {
        $promo = Promo::findOrFail($id);

        $validated = $request->validated();

        $promo->update($validated);

        return response()->json([
            'message' => 'تم تحديث الإعلان بنجاح',
            'data' => $promo
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $promo = Promo::findOrFail($id);
        $promo->delete();

        return response()->json([
            'message' => 'تم حذف الإعلان بنجاح'
        ]);
    }
}
