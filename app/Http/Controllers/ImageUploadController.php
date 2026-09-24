<?php

namespace App\Http\Controllers;

use App\Http\Requests\Upload\DeleteImageRequest;
use App\Http\Requests\Upload\UploadImageRequest;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;

class ImageUploadController extends Controller
{
    public function __construct(protected ImageService $imageService) {}

    /**
     * Upload a product image.
     *
     * رفع صورة المنتج
     */
    public function uploadProductImage(UploadImageRequest $request): JsonResponse
    {
        return $this->handleImageUpload($request, 'products');
    }

    /**
     * Upload a category image.
     *
     * رفع صورة التصنيف
     */
    public function uploadCategoryImage(UploadImageRequest $request): JsonResponse
    {
        return $this->handleImageUpload($request, 'categories');
    }

    /**
     * Upload a generic image.
     *
     * رفع صورة عامة
     */
    public function uploadImage(UploadImageRequest $request): JsonResponse
    {
        return $this->handleImageUpload($request, 'images');
    }

    /**
     * Delete an image.
     *
     * حذف صورة
     */
    public function deleteImage(DeleteImageRequest $request): JsonResponse
    {
        $validated = $request->validated();

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
    protected function handleImageUpload(UploadImageRequest $request, string $folder): JsonResponse
    {
        $validated = $request->validated();

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
