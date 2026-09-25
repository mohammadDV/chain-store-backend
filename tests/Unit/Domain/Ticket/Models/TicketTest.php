<?php

use Domain\Ticket\Models\Ticket;
use Domain\Ticket\Models\TicketMessage;
use Domain\Ticket\Models\TicketSubject;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to user and subject', function () {
    $user = User::factory()->create();
    $subject = TicketSubject::query()->create([
        'title' => 'Billing',
        'user_id' => $user->id,
        'status' => 1,
    ]);
    $ticket = Ticket::query()->create([
        'user_id' => $user->id,
        'subject_id' => $subject->id,
        'status' => Ticket::STATUS_ACTIVE,
    ]);

    expect($ticket->user())->toBeInstanceOf(BelongsTo::class)
        ->and($ticket->user->is($user))->toBeTrue()
        ->and($ticket->subject())->toBeInstanceOf(BelongsTo::class)
        ->and($ticket->subject->is($subject))->toBeTrue();
});

it('has many messages and a first message relation', function () {
    $user = User::factory()->create();
    $subject = TicketSubject::query()->create([
        'title' => 'Support',
        'user_id' => $user->id,
        'status' => 1,
    ]);
    $ticket = Ticket::query()->create([
        'user_id' => $user->id,
        'subject_id' => $subject->id,
        'status' => Ticket::STATUS_ACTIVE,
    ]);
    $first = TicketMessage::query()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $user->id,
        'message' => 'Hello',
        'status' => TicketMessage::PENDING,
    ]);
    TicketMessage::query()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $user->id,
        'message' => 'Follow up',
        'status' => TicketMessage::READ,
    ]);

    expect($ticket->messages())->toBeInstanceOf(HasMany::class)
        ->and($ticket->messages)->toHaveCount(2)
        ->and($ticket->message())->toBeInstanceOf(HasOne::class)
        ->and($ticket->message->is($first))->toBeTrue();
});

it('exposes status constants', function () {
    expect(Ticket::STATUS_ACTIVE)->toBe('active')
        ->and(Ticket::STATUS_CLOSED)->toBe('closed');
});

it('can be closed', function () {
    $user = User::factory()->create();
    $subject = TicketSubject::query()->create([
        'title' => 'Closed topic',
        'user_id' => $user->id,
        'status' => 1,
    ]);
    $ticket = Ticket::query()->create([
        'user_id' => $user->id,
        'subject_id' => $subject->id,
        'status' => Ticket::STATUS_CLOSED,
    ]);

    expect($ticket->status)->toBe(Ticket::STATUS_CLOSED);
});
