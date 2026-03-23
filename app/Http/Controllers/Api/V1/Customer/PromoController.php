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
    public function index(Request $request)
    {
        $lang = $request->header('Accept-Language', 'ar');
        // fallback generic strings
        if (strpos($lang, 'en') !== false) {
            $lang = 'en';
        } else {
            $lang = 'ar';
        }

        $promos = Promo::where('is_active', true)->latest()->get()->map(function ($promo) use ($lang) {
            $promoArray = $promo->toArray();
            
            $getField = function($field) use ($promo, $lang) {
                if (is_array($promo->$field)) {
                    return $promo->$field[$lang] ?? $promo->$field['ar'] ?? $promo->$field['en'] ?? null;
                }
                return $promo->$field;
            };

            $promoArray['title'] = $getField('title');
            $promoArray['subtitle'] = $getField('subtitle');
            $promoArray['button_text'] = $getField('button_text') ?: 'عرض الآن';

            return $promoArray;
        });

        return response()->json($promos);
    }
}
