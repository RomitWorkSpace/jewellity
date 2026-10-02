<?php

namespace App\Modules\Catalog\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Http\Requests\Admin\AttributeRequest;
use App\Modules\Catalog\Http\Resources\AttributeResource;
use App\Modules\Catalog\Models\Attribute;
use App\Modules\Catalog\Models\AttributeValue;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttributeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:products.view', only: ['index']),
            new Middleware('permission:products.update', except: ['index']),
        ];
    }

    public function index()
    {
        return AttributeResource::collection(Attribute::with('values')->orderBy('name')->get());
    }

    public function store(AttributeRequest $request): JsonResponse
    {
        $attribute = DB::transaction(function () use ($request) {
            $attribute = Attribute::create(['name' => $request->validated('name')]);
            $this->syncValues($attribute, $request->validated('values') ?? []);

            return $attribute;
        });

        return (new AttributeResource($attribute->load('values')))->response()->setStatusCode(201);
    }

    public function update(AttributeRequest $request, Attribute $attribute): AttributeResource
    {
        DB::transaction(function () use ($request, $attribute) {
            $attribute->update(['name' => $request->validated('name')]);

            if ($request->has('values')) {
                $this->syncValues($attribute, $request->validated('values') ?? []);
            }
        });

        return new AttributeResource($attribute->load('values'));
    }

    public function destroy(Attribute $attribute): JsonResponse
    {
        $inUse = DB::table('variant_attribute_value')
            ->whereIn('attribute_value_id', $attribute->values()->select('id'))->exists();

        if ($inUse) {
            throw ValidationException::withMessages(['attribute' => 'This attribute is used by existing variants.']);
        }

        $attribute->delete();

        return response()->json(null, 204);
    }

    /** Upserts the given values; values omitted from the list are removed if unused. */
    private function syncValues(Attribute $attribute, array $values): void
    {
        $keep = [];

        foreach ($values as $i => $row) {
            // Match by id, else by slug, so re-sending a value never duplicates it.
            $model = isset($row['id'])
                ? $attribute->values()->findOrFail($row['id'])
                : ($attribute->values()->where('slug', Str::slug($row['value']))->first()
                    ?? new AttributeValue(['attribute_id' => $attribute->id]));

            $model->fill([
                'value' => $row['value'],
                'slug' => Str::slug($row['value']),
                'sort_order' => $row['sort_order'] ?? $i,
            ])->save();

            $keep[] = $model->id;
        }

        $remove = $attribute->values()->whereNotIn('id', $keep)->pluck('id');
        $used = DB::table('variant_attribute_value')->whereIn('attribute_value_id', $remove)->pluck('attribute_value_id');

        if ($used->isNotEmpty()) {
            throw ValidationException::withMessages(['values' => 'Some removed values are used by existing variants.']);
        }

        AttributeValue::whereIn('id', $remove)->delete();
    }
}
