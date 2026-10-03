<?php

namespace App\Http\Controllers\Api;

use App\Actions\RecordSale;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\MenuItem;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class SaleController extends Controller
{
    /** Recent sales, newest first (for the POS screen). */
    public function index(): AnonymousResourceCollection
    {
        return SaleResource::collection(
            Sale::query()
                ->with(['menuItem', 'stockMovements.ingredient'])
                ->orderByDesc('id')
                ->limit(50)
                ->get()
        );
    }

    /**
     * POS entry point: "we sold N of menu item X". Responds 201 with what
     * was consumed and any negative-stock warnings; 200 when an idempotency
     * key is replayed.
     */
    public function store(StoreSaleRequest $request, RecordSale $record): JsonResponse
    {
        $menuItem = MenuItem::findOrFail($request->validated('menu_item_id'));
        $soldAt = $request->validated('sold_at');

        $result = $record(
            $menuItem,
            (int) $request->validated('quantity'),
            $request->validated('idempotency_key'),
            $soldAt ? Carbon::parse($soldAt) : null,
        );

        return (new SaleResource($result->sale->load(['menuItem', 'stockMovements.ingredient'])))
            ->additional([
                'consumed' => $result->consumed,
                'warnings' => $result->warnings,
                'replayed' => $result->replayed,
            ])
            ->response()
            ->setStatusCode($result->replayed ? 200 : 201);
    }
}
