<?php

namespace App\Modules\Catalog\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Actions\AdjustStock;
use App\Modules\Catalog\Enums\StockReason;
use App\Modules\Catalog\Http\Requests\Admin\StockAdjustmentRequest;
use App\Modules\Catalog\Http\Resources\StockMovementResource;
use App\Modules\Catalog\Http\Resources\VariantResource;
use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class StockController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:inventory.view', only: ['index']),
            new Middleware('permission:inventory.manage', only: ['store']),
        ];
    }

    public function index(ProductVariant $variant)
    {
        return StockMovementResource::collection($variant->stockMovements()->paginate(50));
    }

    public function store(StockAdjustmentRequest $request, ProductVariant $variant, AdjustStock $action): JsonResponse
    {
        $data = $request->validated();

        $action->handle(
            $variant,
            $data['quantity_change'],
            StockReason::from($data['reason']),
            $data['note'] ?? null,
            $request->user()->id,
        );

        return (new VariantResource($variant->refresh()))->response()->setStatusCode(201);
    }
}
