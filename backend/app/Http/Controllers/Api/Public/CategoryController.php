<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\ClientCategory;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = ClientCategory::where('is_active', true)
            ->orderBy('base_price', 'asc')
            ->get();

        return $this->successResponse(
            CategoryResource::collection($categories),
            'Daftar kategori konsultasi'
        );
    }
}