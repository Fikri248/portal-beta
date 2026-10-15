<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBnnkRequest;
use App\Models\Bnnk;
use Illuminate\Http\JsonResponse;

class BnnkController extends Controller
{
    public function store(StoreBnnkRequest $request): JsonResponse
    {
        $bnnk = Bnnk::query()->create($request->validated());

        return response()->json([
            'bnnk' => [
                'id' => $bnnk->id,
                'name' => $bnnk->name,
            ],
        ], 201);
    }
}
