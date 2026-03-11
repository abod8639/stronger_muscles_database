<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Promo;

class PromoController extends Controller
{
    /**
     * Display a listing of active promos for customers.
     */
    public function index()
    {
        $promos = Promo::where('is_active', true)->latest()->get();
        return response()->json($promos);
    }
}
