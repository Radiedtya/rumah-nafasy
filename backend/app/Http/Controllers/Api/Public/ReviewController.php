<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\PsikologProfile;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request, string $slug)
    {
        $profile = PsikologProfile::where('slug', $slug)->first();

        if (!$profile) {
            return $this->errorResponse('Psikolog tidak ditemukan', 404);
        }

        $reviews = Review::where('psikolog_id', $profile->user_id)
            ->where('is_published', true)
            ->with('pasien')
            ->orderBy('created_at', 'desc')
            ->paginate(min($request->get('per_page', 10), 50));

        return $this->paginateResponse(
            $reviews,
            'Daftar review psikolog',
            ReviewResource::class
        );
    }
}