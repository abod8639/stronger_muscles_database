<?php

namespace App\Http\Controllers;

use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImageUploadController extends Controller
{
    public function __construct(protected ImageService $imageService) {}

    /**
     * Upload a product image.
     *
     * رفع صورة المنتج
     */
    public function uploadProductImage(Request $request): JsonResponse
    {
        return $this->handleImageUpload($request, 'products');
    }

    /**
     * Upload a category image.
     *
     * رفع صورة التصنيف
     */
    public function uploadCategoryImage(Request $request): JsonResponse
    {
        return $this->handleImageUpload($request, 'categories');
    }

    /**
     * Upload a generic image.
     *
     * رفع صورة عامة
     */
    public function uploadImage(Request $request): JsonResponse
    {
        return $this->handleImageUpload($request, 'images');
    }

    /**
     * Delete an image.
     *
     * حذف صورة
     */
    public function deleteImage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'path' => 'required|string',
        ]);

        try {
            $this->imageService->delete($validated['path']);

            return response()->json([
                'status' => 'success',
                'message' => 'تم حذف الصورة بنجاح',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'مسار الصورة غير صحيح',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطأ في حذف الصورة: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle image upload logic.
     */
    protected function handleImageUpload(Request $request, string $folder): JsonResponse
    {
        $validated = $request->validate($this->imageService->getValidationRules());

        try {
            $result = $this->imageService->upload($validated['image'], $folder);

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ], 201);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في حفظ الصورة',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطأ في رفع الصورة: '.$e->getMessage(),
            ], 500);
        }
    }
}
