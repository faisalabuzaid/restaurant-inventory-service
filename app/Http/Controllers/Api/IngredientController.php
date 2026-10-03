<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIngredientRequest;
use App\Http\Resources\IngredientResource;
use App\Models\Ingredient;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IngredientController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return IngredientResource::collection(
            Ingredient::query()->orderBy('name')->get()
        );
    }

    public function store(StoreIngredientRequest $request): IngredientResource
    {
        $ingredient = Ingredient::create($request->validated());

        return new IngredientResource($ingredient);
    }
}
