<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Promo;

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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|array',
            'subtitle' => 'nullable|array',
            'button_text' => 'nullable|array',
            'image_url' => 'required|string',
            'background_color' => 'required|string',
            'target_url' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

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
    public function update(Request $request, string $id)
    {
        $promo = Promo::findOrFail($id);

        $validated = $request->validate([
            'title' => 'nullable|array',
            'subtitle' => 'nullable|array',
            'button_text' => 'nullable|array',
            'image_url' => 'sometimes|string',
            'background_color' => 'sometimes|string',
            'target_url' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

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
