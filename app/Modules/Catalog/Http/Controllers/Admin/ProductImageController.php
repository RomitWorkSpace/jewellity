<?php

namespace App\Modules\Catalog\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Http\Requests\Admin\ImageRequest;
use App\Modules\Catalog\Http\Resources\ImageResource;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('permission:products.update')];
    }

    public function store(ImageRequest $request, Product $product): JsonResponse
    {
        $disk = config('catalog.images.disk');

        // Server-generated name; the client's filename is never trusted.
        $path = $request->file('image')->store(config('catalog.images.directory')."/{$product->id}", $disk);

        $image = $product->images()->create([
            'path' => $path,
            'alt' => $request->input('alt'),
            'product_variant_id' => $request->input('product_variant_id'),
            'sort_order' => ((int) $product->images()->max('sort_order')) + 1,
        ]);

        return (new ImageResource($image))->response()->setStatusCode(201);
    }

    /** Body: { "order": [imageId, imageId, ...] } — first becomes the primary image. */
    public function reorder(Request $request, Product $product)
    {
        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'distinct'],
        ]);

        DB::transaction(function () use ($data, $product) {
            foreach ($data['order'] as $position => $id) {
                $product->images()->whereKey($id)->update(['sort_order' => $position]);
            }
        });

        return ImageResource::collection($product->images()->get());
    }

    public function destroy(Product $product, ProductImage $image): JsonResponse
    {
        abort_unless($image->product_id === $product->id, 404);

        Storage::disk(config('catalog.images.disk'))->delete($image->path);
        $image->delete();

        return response()->json(null, 204);
    }
}
