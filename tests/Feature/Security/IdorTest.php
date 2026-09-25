<?php

use Domain\Payment\Models\Transaction;
use Domain\Product\Models\Order;
use Domain\Review\Models\Review;
use Domain\Ticket\Models\Ticket;
use Domain\Ticket\Models\TicketSubject;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
});

it('cannot show another users ticket', function () {
    $owner = User::factory()->create();
    $subject = TicketSubject::query()->create([
        'title' => 'Support',
        'user_id' => $owner->id,
        'status' => 1,
    ]);
    $ticket = Ticket::query()->create([
        'user_id' => $owner->id,
        'subject_id' => $subject->id,
        'status' => Ticket::STATUS_ACTIVE,
    ]);

    $this->actingAsUser(User::factory()->create());

    $this->getJson("/api/profile/tickets/{$ticket->id}")
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Unauthorized');
});

it('cannot update another users review', function () {
    $owner = User::factory()->create();
    [, $product] = $this->seedProductWithStock();
    $review = Review::query()->create([
        'user_id' => $owner->id,
        'product_id' => $product->id,
        'comment' => 'Original review',
        'rate' => 4,
        'status' => Review::APPROVED,
    ]);

    $this->actingAsUser(User::factory()->create());

    $this->patchJson("/api/profile/reviews/{$review->id}", [
        'comment' => 'Changed by attacker',
        'rate' => 1,
    ])->assertUnauthorized()->assertJsonPath('message', 'Unauthorized');

    expect($review->fresh()->comment)->toBe('Original review')
        ->and($review->fresh()->rate)->toBe(4);
});

it('cannot pay another users order', function () {
    $owner = User::factory()->create();
    $order = Order::factory()->for($owner)->create();
    $attacker = User::factory()->create();

    $this->actingAsUser($attacker);
    $this->createWalletFor($attacker, 1_000_000);

    $this->postJson("/api/profile/orders/{$order->id}/pay", [
        'payment_method' => Transaction::WALLET,
        'fullname' => 'Attacker',
        'address' => 'Test address',
        'postal_code' => '1234567890',
    ])->assertUnauthorized()->assertJsonPath('message', 'Unauthorized');
});

it('prevents guests from accessing profile routes', function (string $method, string $uri) {
    $this->json($method, $uri)->assertUnauthorized();
})->with([
    ['GET', '/api/profile/orders'],
    ['GET', '/api/profile/wallet'],
    ['GET', '/api/profile/tickets'],
    ['GET', '/api/profile/payment/transactions'],
]);
