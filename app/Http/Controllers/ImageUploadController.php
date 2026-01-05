<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadController extends Controller
{
    /**
     * رفع صورة المنتج
     */
    public function uploadProductImage(Request $request)
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
        ]);

        try {
            $file = $validated['image'];
            $folder = 'products';
            $fileName = Str::uuid().'.'.$file->getClientOriginalExtension();
            $saved = Storage::disk('public')->putFileAs($folder, $file, $fileName);

            if (! $saved) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'فشل في حفظ الصورة',
                ], 500);
            }

            $path = $folder.'/'.$fileName;
            $url = Storage::disk('public')->url($path);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'url' => $url,
                    'path' => $path,
                    'name' => $file->getClientOriginalName(),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطأ في رفع الصورة: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * رفع صورة التصنيف
     */
    public function uploadCategoryImage(Request $request)
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
        ]);

        try {
            $file = $validated['image'];
            $folder = 'categories';
            $fileName = Str::uuid().'.'.$file->getClientOriginalExtension();
            $saved = Storage::disk('public')->putFileAs($folder, $file, $fileName);

            if (! $saved) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'فشل في حفظ الصورة',
                ], 500);
            }

            $path = $folder.'/'.$fileName;
            $url = Storage::disk('public')->url($path);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'url' => $url,
                    'path' => $path,
                    'name' => $file->getClientOriginalName(),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطأ في رفع الصورة: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * رفع صورة عامة
     */
    public function uploadImage(Request $request)
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
        ]);

        try {
            $file = $validated['image'];
            $folder = 'images';
            $fileName = Str::uuid().'.'.$file->getClientOriginalExtension();
            $saved = Storage::disk('public')->putFileAs($folder, $file, $fileName);

            if (! $saved) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'فشل في حفظ الصورة',
                ], 500);
            }

            $path = $folder.'/'.$fileName;
            $url = Storage::disk('public')->url($path);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'url' => $url,
                    'path' => $path,
                    'name' => $file->getClientOriginalName(),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطأ في رفع الصورة: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * حذف صورة
     */
    public function deleteImage(Request $request)
    {
        $validated = $request->validate([
            'path' => 'required|string',
        ]);

        try {
            if (Storage::disk('public')->exists($validated['path'])) {
                Storage::disk('public')->delete($validated['path']);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'تم حذف الصورة بنجاح',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطأ في حذف الصورة: '.$e->getMessage(),
            ], 500);
        }
    }
}
