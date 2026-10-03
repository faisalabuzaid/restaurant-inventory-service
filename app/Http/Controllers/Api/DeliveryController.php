<?php

namespace App\Http\Controllers\Api;

use App\Actions\RecordDelivery;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDeliveryRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class DeliveryController extends Controller
{
    /**
     * Record a delivery and return the updated purchase order, so the client
     * sees the new status and outstanding quantities in one round trip.
     */
    public function store(StoreDeliveryRequest $request, PurchaseOrder $purchaseOrder, RecordDelivery $record): JsonResponse
    {
        $receivedAt = $request->validated('received_at');

        $delivery = $record(
            $purchaseOrder,
            $request->validated('lines'),
            $receivedAt ? Carbon::parse($receivedAt) : null,
            $request->validated('note'),
        );

        return (new PurchaseOrderResource($purchaseOrder->fresh(PurchaseOrder::detailRelations())))
            ->additional(['delivery_id' => $delivery->id])
            ->response()
            ->setStatusCode(201);
    }
}
