<?php

namespace App\Actions;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;

/**
 * Manual transition draft -> sent. Locks the row so two concurrent "send"
 * clicks cannot both succeed.
 */
class SendPurchaseOrder
{
    public function __invoke(PurchaseOrder $order): PurchaseOrder
    {
        return DB::transaction(function () use ($order) {
            $order = PurchaseOrder::query()->lockForUpdate()->findOrFail($order->id);

            $order->transitionTo(PurchaseOrderStatus::Sent);
            $order->save();

            return $order;
        });
    }
}
