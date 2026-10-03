<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMenuItemRequest;
use App\Http\Requests\UpdateRecipeRequest;
use App\Http\Resources\MenuItemResource;
use App\Models\MenuItem;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class MenuItemController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return MenuItemResource::collection(
            MenuItem::query()
                ->with('recipeLines.ingredient')
                ->orderBy('name')
                ->get()
        );
    }

    public function store(StoreMenuItemRequest $request): MenuItemResource
    {
        $menuItem = DB::transaction(function () use ($request) {
            $menuItem = MenuItem::create(['name' => $request->validated('name')]);
            $menuItem->replaceRecipe($request->validated('lines'));

            return $menuItem;
        });

        return new MenuItemResource($menuItem->load('recipeLines.ingredient'));
    }

    public function show(MenuItem $menuItem): MenuItemResource
    {
        return new MenuItemResource($menuItem->load('recipeLines.ingredient'));
    }

    public function updateRecipe(UpdateRecipeRequest $request, MenuItem $menuItem): MenuItemResource
    {
        DB::transaction(fn () => $menuItem->replaceRecipe($request->validated('lines')));

        return new MenuItemResource($menuItem->load('recipeLines.ingredient'));
    }
}
