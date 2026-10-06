<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate(['search' => ['sometimes', 'nullable', 'string', 'max:255'], 'category_id' => ['sometimes', 'nullable', 'integer', 'min:1'], 'merchant' => ['sometimes', 'nullable', 'string', 'max:255'], 'sort' => ['sometimes', Rule::in(['name', 'created_at', 'amount', 'purchase_date'])], 'direction' => ['sometimes', Rule::in(['asc', 'desc'])], 'page' => ['sometimes', 'integer', 'min:1'], 'per_page' => ['sometimes', 'integer', 'min:1', 'max:100']]);
        $query = $request->user()->products()->with('category')->withCount('documents');
        if (! empty($data['search'])) {
            $search = '%'.$data['search'].'%';
            $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', $search)->orWhere('merchant', 'like', $search)->orWhere('note', 'like', $search);
            });
        }
        foreach (['category_id', 'merchant'] as $field) {
            if (isset($data[$field])) {
                $query->where($field, $data[$field]);
            }
        }
        $query->orderBy($data['sort'] ?? 'created_at', $data['direction'] ?? 'desc')->orderBy('id', 'desc');

        return $this->paginated($query->paginate($request->integer('per_page', 20)), ProductResource::class);
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $product = $request->user()->products()->create($request->validated());

        return response()->json(new ProductResource($product->load('category')->loadCount('documents')), 201);
    }

    public function show(Request $request, Product $product): ProductResource
    {
        abort_unless($product->user_id === $request->user()->id, 404);
        Gate::authorize('view', $product);

        return new ProductResource($product->load(['category', 'documents.category'])->loadCount('documents'));
    }

    public function update(ProductRequest $request, Product $product): ProductResource
    {
        Gate::authorize('update', $product);
        $product->update($request->validated());

        return $this->show($request, $product);
    }

    public function destroy(Request $request, Product $product): Response
    {
        abort_unless($product->user_id === $request->user()->id, 404);
        Gate::authorize('delete', $product);
        $request->validate(['confirmed' => ['required', 'accepted']]);
        $product->delete();

        return response()->noContent();
    }
}
