<?php

namespace App\Modules\Catalog\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Actions\SaveVariant;
use App\Modules\Catalog\Http\Requests\Admin\VariantRequest;
use App\Modules\Catalog\Http\Resources\VariantResource;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;

class ProductVariantController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('permission:products.update')];
    }

    public function store(VariantRequest $request, Product $product, SaveVariant $action): JsonResponse
    {
        $variant = $action->create($product, $request->validated(), $request->user()->id);

        return (new VariantResource($variant))->response()->setStatusCode(201);
    }

    public function update(VariantRequest $request, Product $product, ProductVariant $variant, SaveVariant $action): VariantResource
    {
        $this->ensureBelongs($product, $variant);

        return new VariantResource($action->update($variant, $request->validated()));
    }

    public function destroy(Product $product, ProductVariant $variant, SaveVariant $action): JsonResponse
    {
        $this->ensureBelongs($product, $variant);

        if ($product->variants()->count() <= 1) {
            throw ValidationException::withMessages(['variant' => 'A product must keep at least one variant.']);
        }

        $action->delete($variant);

        return response()->json(null, 204);
    }

    private function ensureBelongs(Product $product, ProductVariant $variant): void
    {
        abort_unless($variant->product_id === $product->id, 404);
    }
}
