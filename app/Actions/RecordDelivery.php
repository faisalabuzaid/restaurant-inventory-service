<?php

namespace App\Actions;

use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementType;
use App\Exceptions\InvalidOperation;
use App\Exceptions\InvalidStateTransition;
use App\Models\Delivery;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Record what physically arrived against a purchase order.
 *
 * Invariants enforced here, all inside one transaction:
 *  - only orders in `sent` or `received` accept deliveries (409 otherwise)
 *  - a line can never be received beyond what is outstanding (422, nothing written)
 *  - each delivered line produces exactly one positive stock movement
 *  - status follows the quantities: first delivery moves sent -> received,
 *    and the delivery that completes every line moves on to closed
 */
class RecordDelivery
{
    /**
     * @param  array<int, array{purchase_order_line_id: int, quantity: numeric}>  $lines
     */
    public function __invoke(
        PurchaseOrder $order,
        array $lines,
        ?CarbonInterface $receivedAt = null,
        ?string $note = null,
    ): Delivery {
        return DB::transaction(function () use ($order, $lines, $receivedAt, $note) {
            $order = PurchaseOrder::query()->lockForUpdate()->findOrFail($order->id);

            if (! $order->status->acceptsDeliveries()) {
                throw new InvalidStateTransition(
                    "Purchase order #{$order->id} is {$order->status->value}; deliveries can only be recorded for sent orders.",
                    from: $order->status->value,
                );
            }

            /** @var Collection<int, PurchaseOrderLine> $orderLines */
            $orderLines = $order->lines()->withQuantityReceived()->get()->keyBy('id');

            // Validate every line before writing anything.
            foreach ($lines as $line) {
                $orderLine = $orderLines->get($line['purchase_order_line_id'])
                    ?? throw new InvalidOperation(
                        "Line {$line['purchase_order_line_id']} does not belong to purchase order #{$order->id}.",
                        errorCode: 'line_not_in_order',
                    );

                $quantity = $this->decimal($line['quantity']);

                if (bccomp($quantity, $orderLine->outstanding(), 3) > 0) {
                    throw new InvalidOperation(
                        "Cannot receive {$quantity} for {$orderLine->ingredient->name}: only {$orderLine->outstanding()} outstanding.",
                        errorCode: 'over_receipt',
                        context: [
                            'purchase_order_line_id' => $orderLine->id,
                            'outstanding' => (float) $orderLine->outstanding(),
                            'requested' => (float) $quantity,
                        ],
                    );
                }
            }

            $delivery = $order->deliveries()->create([
                'received_at' => $receivedAt ?? now(),
                'note' => $note,
            ]);

            foreach ($lines as $line) {
                $orderLine = $orderLines->get($line['purchase_order_line_id']);
                $quantity = $this->decimal($line['quantity']);

                $deliveryLine = $delivery->lines()->create([
                    'purchase_order_line_id' => $orderLine->id,
                    'quantity' => $quantity,
                ]);

                $deliveryLine->stockMovement()->create([
                    'ingredient_id' => $orderLine->ingredient_id,
                    'quantity' => $quantity,
                    'type' => StockMovementType::Delivery,
                ]);
            }

            $this->advanceStatus($order);

            return $delivery;
        });
    }

    /**
     * Decide the new status from the ledger, not from the request: re-read the
     * received totals and walk the linear state machine as far as they allow.
     */
    private function advanceStatus(PurchaseOrder $order): void
    {
        if ($order->status === PurchaseOrderStatus::Sent) {
            $order->transitionTo(PurchaseOrderStatus::Received);
        }

        $allReceived = $order->lines()->withQuantityReceived()->get()
            ->every(fn (PurchaseOrderLine $line) => $line->isFullyReceived());

        if ($allReceived) {
            $order->transitionTo(PurchaseOrderStatus::Closed);
        }

        $order->save();
    }

    private function decimal(mixed $value): string
    {
        return number_format((float) $value, 3, '.', '');
    }
}
