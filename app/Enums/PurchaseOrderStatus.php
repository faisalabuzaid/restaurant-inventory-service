<?php

namespace App\Enums;

/**
 * Purchase order lifecycle. Strictly linear:
 *
 *   draft -> sent -> received -> closed
 *
 * - draft:    being composed, nothing has been ordered yet
 * - sent:     sent to the supplier, nothing has arrived yet
 * - received: at least one delivery recorded, something still outstanding
 * - closed:   every line fully received (terminal)
 *
 * Only draft -> sent is a manual action. The other two moves are decided by
 * the delivery-recording logic from the received quantities. A single
 * delivery that completes an order passes through `received` to `closed`
 * within one transaction, so the enum itself only knows the linear edges.
 */
enum PurchaseOrderStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Received = 'received';
    case Closed = 'closed';

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Sent],
            self::Sent => [self::Received],
            self::Received => [self::Closed],
            self::Closed => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    /** Still expecting goods (or not yet ordered). */
    public function isOpen(): bool
    {
        return $this !== self::Closed;
    }

    /** Deliveries may be recorded against orders in this state. */
    public function acceptsDeliveries(): bool
    {
        return $this === self::Sent || $this === self::Received;
    }

    /** Human label; `received` reads as "partially received" to avoid ambiguity. */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::Received => 'Partially received',
            self::Closed => 'Closed',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $status) => $status->value, self::cases());
    }
}
