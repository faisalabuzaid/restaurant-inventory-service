<?php

namespace Database\Factories;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'status' => PurchaseOrderStatus::Draft,
        ];
    }

    public function sent(): static
    {
        return $this->state(['status' => PurchaseOrderStatus::Sent, 'sent_at' => now()]);
    }

    public function closed(): static
    {
        return $this->state([
            'status' => PurchaseOrderStatus::Closed,
            'sent_at' => now()->subDay(),
            'closed_at' => now(),
        ]);
    }

    /**
     * Attach lines: [ingredient_id => quantity_ordered, ...].
     *
     * @param  array<int, numeric>  $quantitiesByIngredientId
     */
    public function withLines(array $quantitiesByIngredientId): static
    {
        return $this->afterCreating(function (PurchaseOrder $order) use ($quantitiesByIngredientId) {
            foreach ($quantitiesByIngredientId as $ingredientId => $quantity) {
                $order->lines()->create([
                    'ingredient_id' => $ingredientId,
                    'quantity_ordered' => $quantity,
                ]);
            }
        });
    }
}
