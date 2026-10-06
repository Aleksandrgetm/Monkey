<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => CategoryResource::collection($request->user()->categories()->withCount(['documents', 'products'])->orderBy('name')->get())]);
    }

    public function store(CategoryRequest $request): JsonResponse
    {
        return response()->json(new CategoryResource($request->user()->categories()->create($request->validated())), 201);
    }

    public function show(Request $request, Category $category): CategoryResource
    {
        abort_unless($category->user_id === $request->user()->id, 404);
        Gate::authorize('view', $category);

        return new CategoryResource($category->loadCount(['documents', 'products']));
    }

    public function update(CategoryRequest $request, Category $category): CategoryResource
    {
        Gate::authorize('update', $category);
        $category->update($request->validated());

        return new CategoryResource($category->loadCount(['documents', 'products']));
    }

    public function destroy(Request $request, Category $category): Response
    {
        abort_unless($category->user_id === $request->user()->id, 404);
        Gate::authorize('delete', $category);
        $request->validate(['confirmed' => ['required', 'accepted']]);
        if ($category->documents()->exists() || $category->products()->exists()) {
            throw ValidationException::withMessages(['category' => 'Move or delete the documents and products in this category before deleting it.']);
        }
        $category->delete();

        return response()->noContent();
    }
}
