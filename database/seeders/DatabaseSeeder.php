<?php

namespace Database\Seeders;

use App\Actions\RecordDelivery;
use App\Actions\RecordSale;
use App\Actions\SendPurchaseOrder;
use App\Enums\Unit;
use App\Models\Ingredient;
use App\Models\MenuItem;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

/**
 * A small, realistic burger-restaurant scenario so the UI has something to
 * show on first boot. Goes through the same Actions as the API, so the
 * ledger and order statuses are produced by the real rules.
 *
 * Idempotent: only runs when the database is empty, because the container
 * entrypoint calls `migrate --seed` on every start.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (Ingredient::query()->exists()) {
            $this->command?->info('Database already seeded, skipping.');

            return;
        }

        $beef = Ingredient::create(['name' => 'Beef patty mix', 'unit' => Unit::Gram]);
        $bun = Ingredient::create(['name' => 'Burger bun', 'unit' => Unit::Piece]);
        $cheese = Ingredient::create(['name' => 'Cheddar cheese', 'unit' => Unit::Gram]);
        $lettuce = Ingredient::create(['name' => 'Lettuce', 'unit' => Unit::Gram]);
        $tomato = Ingredient::create(['name' => 'Tomato', 'unit' => Unit::Gram]);
        $fries = Ingredient::create(['name' => 'Frozen fries', 'unit' => Unit::Kilogram]);

        $meats = Supplier::create(['name' => 'Al Watania Meats', 'contact' => 'orders@watania.example']);
        $bakery = Supplier::create(['name' => 'Golden Bakery', 'contact' => '+966 50 123 4567']);
        $produce = Supplier::create(['name' => 'Fresh Fields Produce']);

        $classic = MenuItem::create(['name' => 'Classic Burger']);
        $classic->replaceRecipe([
            ['ingredient_id' => $beef->id, 'quantity' => 150],
            ['ingredient_id' => $bun->id, 'quantity' => 1],
            ['ingredient_id' => $cheese->id, 'quantity' => 20],
        ]);

        $deluxe = MenuItem::create(['name' => 'Deluxe Cheeseburger']);
        $deluxe->replaceRecipe([
            ['ingredient_id' => $beef->id, 'quantity' => 200],
            ['ingredient_id' => $bun->id, 'quantity' => 1],
            ['ingredient_id' => $cheese->id, 'quantity' => 40],
            ['ingredient_id' => $lettuce->id, 'quantity' => 20],
            ['ingredient_id' => $tomato->id, 'quantity' => 30],
        ]);

        $friesItem = MenuItem::create(['name' => 'Fries']);
        $friesItem->replaceRecipe([
            ['ingredient_id' => $fries->id, 'quantity' => 0.15],
        ]);

        $send = app(SendPurchaseOrder::class);
        $deliver = app(RecordDelivery::class);
        $sell = app(RecordSale::class);

        // 1. Fully delivered and closed: this is where the opening stock comes from.
        $meatOrder = $this->order($meats, [$beef->id => 6000, $cheese->id => 1500], 'Weekly meat order');
        $send($meatOrder);
        $deliver($meatOrder, $this->allLines($meatOrder), now()->subDays(2), 'Complete');

        $bunOrder = $this->order($bakery, [$bun->id => 60]);
        $send($bunOrder);
        $deliver($bunOrder, $this->allLines($bunOrder), now()->subDays(2));

        // 2. Partially received: supplier short-shipped the tomatoes.
        $produceOrder = $this->order($produce, [$lettuce->id => 2000, $tomato->id => 3000, $fries->id => 10]);
        $send($produceOrder);
        $deliver($produceOrder, [
            ['purchase_order_line_id' => $this->lineFor($produceOrder, $lettuce)->id, 'quantity' => 2000],
            ['purchase_order_line_id' => $this->lineFor($produceOrder, $tomato)->id, 'quantity' => 1200],
            ['purchase_order_line_id' => $this->lineFor($produceOrder, $fries)->id, 'quantity' => 10],
        ], now()->subDay(), 'Only 1.2 kg of tomatoes arrived; rest to follow');

        // 3. Sent, nothing arrived yet.
        $send($this->order($bakery, [$bun->id => 120], 'Weekend top-up'));

        // 4. Still a draft.
        $this->order($meats, [$beef->id => 4000, $cheese->id => 1000]);

        // A morning of sales.
        $sell($classic, 12, 'seed-sale-1', now()->subHours(3));
        $sell($deluxe, 5, 'seed-sale-2', now()->subHours(2));
        $sell($friesItem, 9, 'seed-sale-3', now()->subHour());
    }

    /** @param  array<int, numeric>  $quantitiesByIngredientId */
    private function order(Supplier $supplier, array $quantitiesByIngredientId, ?string $notes = null): PurchaseOrder
    {
        $order = PurchaseOrder::create(['supplier_id' => $supplier->id, 'notes' => $notes]);

        foreach ($quantitiesByIngredientId as $ingredientId => $quantity) {
            $order->lines()->create(['ingredient_id' => $ingredientId, 'quantity_ordered' => $quantity]);
        }

        return $order;
    }

    /** @return array<int, array{purchase_order_line_id: int, quantity: string}> */
    private function allLines(PurchaseOrder $order): array
    {
        return $order->lines->map(fn ($line) => [
            'purchase_order_line_id' => $line->id,
            'quantity' => $line->quantity_ordered,
        ])->all();
    }

    private function lineFor(PurchaseOrder $order, Ingredient $ingredient)
    {
        return $order->lines()->where('ingredient_id', $ingredient->id)->firstOrFail();
    }
}
