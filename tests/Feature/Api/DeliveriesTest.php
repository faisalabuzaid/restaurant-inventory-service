<?php

use App\Enums\PurchaseOrderStatus;
use App\Enums\Unit;
use App\Models\Ingredient;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Supplier;

beforeEach(function () {
    $this->supplier = Supplier::factory()->create();
    $this->beef = Ingredient::factory()->unit(Unit::Gram)->create(['name' => 'Beef']);
    $this->bun = Ingredient::factory()->unit(Unit::Piece)->create(['name' => 'Bun']);

    $this->order = PurchaseOrder::factory()
        ->for($this->supplier)
        ->sent()
        ->withLines([$this->beef->id => 1000, $this->bun->id => 40])
        ->create();

    $this->beefLine = $this->order->lines()->where('ingredient_id', $this->beef->id)->first();
    $this->bunLine = $this->order->lines()->where('ingredient_id', $this->bun->id)->first();
});

function deliver(PurchaseOrder $order, array $lines, array $extra = [])
{
    return test()->postJson("/api/purchase-orders/{$order->id}/deliveries", ['lines' => $lines, ...$extra]);
}

it('records a partial delivery: stock goes up, order becomes partially received', function () {
    $response = deliver($this->order, [
        ['purchase_order_line_id' => $this->beefLine->id, 'quantity' => 400],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'received')
        ->assertJsonPath('data.status_label', 'Partially received')
        ->assertJsonPath('data.is_open', true)
        ->assertJsonPath('data.lines.0.quantity_received', 400)
        ->assertJsonPath('data.lines.0.outstanding', 600)
        ->assertJsonPath('data.lines.1.quantity_received', 0)
        ->assertJsonPath('data.lines.1.outstanding', 40)
        ->assertJsonCount(1, 'data.deliveries')
        ->assertJsonPath('data.deliveries.0.lines.0.quantity', 400);

    expect($this->beef->currentStock())->toBe('400.000')
        ->and($this->bun->currentStock())->toBe('0.000')
        ->and(StockMovement::count())->toBe(1)
        ->and($this->order->fresh()->closed_at)->toBeNull();
});

it('closes the order when the final delivery completes every line', function () {
    deliver($this->order, [['purchase_order_line_id' => $this->beefLine->id, 'quantity' => 400]])->assertCreated();

    $response = deliver($this->order, [
        ['purchase_order_line_id' => $this->beefLine->id, 'quantity' => 600],
        ['purchase_order_line_id' => $this->bunLine->id, 'quantity' => 40],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'closed')
        ->assertJsonPath('data.is_open', false)
        ->assertJsonPath('data.accepts_deliveries', false)
        ->assertJsonPath('data.lines.0.outstanding', 0)
        ->assertJsonPath('data.lines.1.outstanding', 0)
        ->assertJsonCount(2, 'data.deliveries');

    expect($this->beef->currentStock())->toBe('1000.000')
        ->and($this->bun->currentStock())->toBe('40.000')
        ->and(StockMovement::count())->toBe(3)
        ->and($this->order->fresh()->closed_at)->not->toBeNull();
});

it('closes a sent order directly when one delivery covers everything', function () {
    deliver($this->order, [
        ['purchase_order_line_id' => $this->beefLine->id, 'quantity' => 1000],
        ['purchase_order_line_id' => $this->bunLine->id, 'quantity' => 40],
    ])->assertCreated()
        ->assertJsonPath('data.status', 'closed');

    expect($this->order->fresh()->status)->toBe(PurchaseOrderStatus::Closed);
});

it('handles fractional quantities exactly', function () {
    $flour = Ingredient::factory()->unit(Unit::Kilogram)->create();
    $order = PurchaseOrder::factory()->for($this->supplier)->sent()->withLines([$flour->id => 1])->create();
    $line = $order->lines()->first();

    deliver($order, [['purchase_order_line_id' => $line->id, 'quantity' => 0.1]])->assertCreated();
    deliver($order, [['purchase_order_line_id' => $line->id, 'quantity' => 0.2]])->assertCreated();
    deliver($order, [['purchase_order_line_id' => $line->id, 'quantity' => 0.7]])
        ->assertCreated()
        ->assertJsonPath('data.status', 'closed')
        ->assertJsonPath('data.lines.0.outstanding', 0);

    expect($flour->currentStock())->toBe('1.000');
});

it('rejects receiving more than is outstanding and writes nothing', function () {
    deliver($this->order, [['purchase_order_line_id' => $this->beefLine->id, 'quantity' => 400]])->assertCreated();

    deliver($this->order, [['purchase_order_line_id' => $this->beefLine->id, 'quantity' => 600.001]])
        ->assertUnprocessable()
        ->assertJsonPath('error', 'over_receipt')
        ->assertJsonPath('outstanding', 600)
        ->assertJsonPath('requested', 600.001);

    expect(StockMovement::count())->toBe(1)
        ->and($this->beef->currentStock())->toBe('400.000')
        ->and($this->order->deliveries()->count())->toBe(1)
        ->and($this->order->fresh()->status)->toBe(PurchaseOrderStatus::Received);
});

it('is atomic: one bad line rejects the whole delivery', function () {
    deliver($this->order, [
        ['purchase_order_line_id' => $this->beefLine->id, 'quantity' => 100],   // fine
        ['purchase_order_line_id' => $this->bunLine->id, 'quantity' => 41],     // over
    ])->assertUnprocessable()
        ->assertJsonPath('error', 'over_receipt')
        ->assertJsonPath('purchase_order_line_id', $this->bunLine->id);

    expect(StockMovement::count())->toBe(0)
        ->and($this->order->deliveries()->count())->toBe(0)
        ->and($this->order->fresh()->status)->toBe(PurchaseOrderStatus::Sent);
});

it('refuses deliveries against a draft order', function () {
    $draft = PurchaseOrder::factory()->for($this->supplier)->withLines([$this->beef->id => 10])->create();
    $line = $draft->lines()->first();

    deliver($draft, [['purchase_order_line_id' => $line->id, 'quantity' => 5]])
        ->assertStatus(409)
        ->assertJsonPath('error', 'invalid_state_transition')
        ->assertJsonPath('from', 'draft');

    expect(StockMovement::count())->toBe(0);
});

it('refuses deliveries against a closed order', function () {
    deliver($this->order, [
        ['purchase_order_line_id' => $this->beefLine->id, 'quantity' => 1000],
        ['purchase_order_line_id' => $this->bunLine->id, 'quantity' => 40],
    ])->assertCreated();

    deliver($this->order, [['purchase_order_line_id' => $this->beefLine->id, 'quantity' => 1]])
        ->assertStatus(409)
        ->assertJsonPath('from', 'closed');

    expect(StockMovement::count())->toBe(2);
});

it('rejects lines that belong to another purchase order', function () {
    $other = PurchaseOrder::factory()->for($this->supplier)->sent()->withLines([$this->beef->id => 5])->create();
    $foreignLine = $other->lines()->first();

    deliver($this->order, [['purchase_order_line_id' => $foreignLine->id, 'quantity' => 1]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['lines.0.purchase_order_line_id']);
});

it('requires at least one line with a positive quantity', function () {
    deliver($this->order, [])->assertUnprocessable()->assertJsonValidationErrors(['lines']);

    deliver($this->order, [['purchase_order_line_id' => $this->beefLine->id, 'quantity' => 0]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['lines.0.quantity']);
});

it('stores the received_at and note when given', function () {
    deliver($this->order, [['purchase_order_line_id' => $this->bunLine->id, 'quantity' => 10]], [
        'received_at' => '2026-10-01T08:30:00Z',
        'note' => 'Driver was late',
    ])->assertCreated()
        ->assertJsonPath('data.deliveries.0.note', 'Driver was late')
        ->assertJsonPath('data.deliveries.0.received_at', '2026-10-01T08:30:00.000000Z');
});

it('links every stock movement to its delivery line', function () {
    deliver($this->order, [['purchase_order_line_id' => $this->beefLine->id, 'quantity' => 250]])->assertCreated();

    $movement = StockMovement::sole();

    expect($movement->type->value)->toBe('delivery')
        ->and((float) $movement->quantity)->toBe(250.0)
        ->and($movement->ingredient_id)->toBe($this->beef->id)
        ->and($movement->source->purchase_order_line_id)->toBe($this->beefLine->id);
});
