<?php

namespace App\Modules\Catalog\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Actions\CreateProduct;
use App\Modules\Catalog\Enums\ProductStatus;
use App\Modules\Catalog\Http\Requests\Admin\ProductRequest;
use App\Modules\Catalog\Http\Resources\ProductResource;
use App\Modules\Catalog\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;

class ProductController extends Controller implements HasMiddleware
{
    private const WITH = ['variants.attributeValues.attribute', 'categories', 'images'];

    public static function middleware(): array
    {
        return [
            new Middleware('permission:products.view', only: ['index', 'show']),
            new Middleware('permission:products.create', only: ['store']),
            new Middleware('permission:products.update', only: ['update']),
            new Middleware('permission:products.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $request->validate([
            'status' => ['nullable', Rule::enum(ProductStatus::class)],
            'category_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $products = Product::query()
            ->with(['variants', 'images', 'categories'])
            ->search($request->query('q'))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('category_id'), fn ($q, $id) => $q->whereHas('categories', fn ($c) => $c->whereKey($id)))
            ->latest('id')
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();

        return ProductResource::collection($products);
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load(self::WITH));
    }

    public function store(ProductRequest $request, CreateProduct $action): JsonResponse
    {
        $product = $action->handle($request->validated(), $request->user()->id);

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    /** Product-level fields and categories. Variants/stock/images have their own endpoints. */
    public function update(ProductRequest $request, Product $product): ProductResource
    {
        $data = $request->validated();

        if (array_key_exists('category_ids', $data)) {
            $product->categories()->sync($data['category_ids'] ?? []);
        }

        $product->fill(collect($data)->except('category_ids')->all());

        // Stamp first publication.
        if ($product->status === ProductStatus::Active && ! $product->published_at) {
            $product->published_at = now();
        }
        $product->save();

        return new ProductResource($product->load(self::WITH));
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(null, 204);
    }
}
