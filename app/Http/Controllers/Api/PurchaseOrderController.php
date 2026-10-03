<?php

namespace App\Http\Controllers\Api;

use App\Actions\SendPurchaseOrder;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = PurchaseOrder::query()
            ->with(PurchaseOrder::detailRelations())
            ->when($request->boolean('open'), fn ($query) => $query->open())
            ->orderByDesc('id')
            ->get();

        return PurchaseOrderResource::collection($orders);
    }

    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        $order = DB::transaction(function () use ($request) {
            $order = PurchaseOrder::create([
                'supplier_id' => $request->validated('supplier_id'),
                'notes' => $request->validated('notes'),
            ]);

            $order->lines()->createMany(
                collect($request->validated('lines'))->map(fn (array $line) => [
                    'ingredient_id' => $line['ingredient_id'],
                    'quantity_ordered' => $line['quantity'],
                ])->all()
            );

            return $order;
        });

        return (new PurchaseOrderResource($order->fresh(PurchaseOrder::detailRelations())))
            ->response()
            ->setStatusCode(201);
    }

    public function show(PurchaseOrder $purchaseOrder): PurchaseOrderResource
    {
        return new PurchaseOrderResource($purchaseOrder->load(PurchaseOrder::detailRelations()));
    }

    public function send(PurchaseOrder $purchaseOrder, SendPurchaseOrder $send): PurchaseOrderResource
    {
        $order = $send($purchaseOrder);

        return new PurchaseOrderResource($order->fresh(PurchaseOrder::detailRelations()));
    }
}
