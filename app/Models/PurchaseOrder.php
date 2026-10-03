<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use App\Exceptions\InvalidStateTransition;
use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use HasFactory;

    protected $fillable = ['supplier_id', 'status', 'notes'];

    /** New orders always start as drafts, also in memory before the first save. */
    protected $attributes = [
        'status' => PurchaseOrderStatus::Draft->value,
    ];

    protected function casts(): array
    {
        return [
            'status' => PurchaseOrderStatus::class,
            'sent_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** @return HasMany<PurchaseOrderLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    /** @return HasMany<Delivery, $this> */
    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    /**
     * Everything the API needs to render an order: supplier, lines with their
     * received totals, and the delivery history.
     *
     * @return array<int|string, mixed>
     */
    public static function detailRelations(): array
    {
        return [
            'supplier',
            'lines' => fn ($query) => $query->withQuantityReceived(),
            'lines.ingredient',
            'deliveries' => fn ($query) => $query->orderBy('received_at')->orderBy('id'),
            'deliveries.lines.purchaseOrderLine.ingredient',
        ];
    }

    /** @param  Builder<PurchaseOrder>  $query */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', '!=', PurchaseOrderStatus::Closed);
    }

    /**
     * The single place where status changes. Enforces the state machine and
     * stamps the lifecycle timestamps. Does not persist; callers save inside
     * their transaction.
     *
     * @throws InvalidStateTransition
     */
    public function transitionTo(PurchaseOrderStatus $to): void
    {
        if (! $this->status->canTransitionTo($to)) {
            throw new InvalidStateTransition(
                "Purchase order #{$this->id} cannot move from {$this->status->value} to {$to->value}.",
                from: $this->status->value,
                to: $to->value,
            );
        }

        $this->status = $to;

        match ($to) {
            PurchaseOrderStatus::Sent => $this->sent_at = now(),
            PurchaseOrderStatus::Closed => $this->closed_at = now(),
            default => null,
        };
    }
}
