<?php

use App\Models\Supplier;

it('creates a supplier', function () {
    $this->postJson('/api/suppliers', ['name' => 'Fresh Farms', 'contact' => '+966 50 000 0000'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Fresh Farms')
        ->assertJsonPath('data.contact', '+966 50 000 0000');

    $this->assertDatabaseHas('suppliers', ['name' => 'Fresh Farms']);
});

it('creates a supplier without contact details', function () {
    $this->postJson('/api/suppliers', ['name' => 'Bakery Co'])
        ->assertCreated()
        ->assertJsonPath('data.contact', null);
});

it('lists suppliers ordered by name', function () {
    Supplier::factory()->create(['name' => 'Zed Meats']);
    Supplier::factory()->create(['name' => 'Alpha Dairy']);

    $this->getJson('/api/suppliers')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Alpha Dairy');
});

it('rejects a duplicate supplier name', function () {
    Supplier::factory()->create(['name' => 'Fresh Farms']);

    $this->postJson('/api/suppliers', ['name' => 'Fresh Farms'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});
