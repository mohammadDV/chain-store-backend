<?php

use App\Filament\Resources\OrderResource\Pages\ListOrders;
use Domain\Product\Enums\OrderLedgerType;
use Domain\Product\Models\Order;
use Domain\Product\Models\OrderLedger;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
    $this->actingAsAdmin();
});

it('lists orders', function () {
    livewire(ListOrders::class)->assertSuccessful();
});

it('changes an order status from the list table', function () {
    $buyer = User::factory()->create(['status' => 1]);
    $order = Order::factory()->create([
        'user_id' => $buyer->id,
        'status' => Order::PENDING,
    ]);

    livewire(ListOrders::class)
        ->callTableAction('change_status', $order, data: [
            'status' => Order::PAID,
            'message' => 'marked paid by admin',
        ])
        ->assertHasNoTableActionErrors();

    expect($order->fresh()->status)->toBe(Order::PAID)
        ->and(OrderLedger::query()
            ->where('order_id', $order->id)
            ->where('type', OrderLedgerType::Paid)
            ->where('message', 'marked paid by admin')
            ->exists())->toBeTrue();
});
