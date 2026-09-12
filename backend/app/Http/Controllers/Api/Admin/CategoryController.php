<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\ClientCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = ClientCategory::withCount('orders');

        if ($request->filled('with_trashed') && $request->with_trashed) {
            $query->withTrashed();
        }

        $categories = $query->orderBy('base_price', 'asc')->get();

        return $this->successResponse(CategoryResource::collection($categories), 'Daftar kategori');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:client_categories,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category = ClientCategory::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'base_price' => $request->base_price,
            'is_active' => $request->get('is_active', true),
        ]);

        return $this->successResponse(new CategoryResource($category), 'Kategori dibuat', 201);
    }

    public function show(ClientCategory $category)
    {
        $category->loadCount('orders');

        return $this->successResponse(new CategoryResource($category), 'Detail kategori');
    }

    public function update(Request $request, ClientCategory $category)
    {
        $request->validate([
            'name' => ['sometimes', 'string', 'max:255', 'unique:client_categories,name,' . $category->id],
            'description' => ['nullable', 'string', 'max:1000'],
            'base_price' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data = $request->only(['name', 'description', 'base_price', 'is_active']);

        if ($request->filled('name')) {
            $data['slug'] = Str::slug($request->name);
        }

        $category->update($data);

        return $this->successResponse(new CategoryResource($category->fresh()), 'Kategori diperbarui');
    }

    public function destroy(ClientCategory $category)
    {
        if ($category->orders()->exists()) {
            return $this->errorResponse('Kategori tidak dapat dihapus, masih digunakan oleh pesanan', 422);
        }

        $category->delete();

        return $this->successResponse(null, 'Kategori dihapus');
    }
}