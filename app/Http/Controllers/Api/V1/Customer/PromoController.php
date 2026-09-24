<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Services\PromoService;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    public function __construct(
        protected PromoService $promoService
    ) {}

    /**
     * Display a listing of active promos for customers.
     */
    public function index(Request $request)
    {
        $lang = $request->header('Accept-Language', 'ar');

        $promos = $this->promoService->getActivePromosForCustomer($lang);

        return response()->json($promos);
    }
}
