<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupplierController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return SupplierResource::collection(
            Supplier::query()->orderBy('name')->get()
        );
    }

    public function store(StoreSupplierRequest $request): SupplierResource
    {
        $supplier = Supplier::create($request->validated());

        return new SupplierResource($supplier);
    }
}
