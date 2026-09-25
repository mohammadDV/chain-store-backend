<?php

use Domain\Ticket\Models\Ticket;
use Domain\Ticket\Models\TicketMessage;
use Domain\Ticket\Models\TicketSubject;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
});

function createTicketSubject(?User $user = null): TicketSubject
{
    $user ??= User::factory()->create();

    return TicketSubject::query()->create([
        'title' => 'Support',
        'user_id' => $user->id,
        'status' => 1,
    ]);
}

it('creates a ticket for authenticated user', function () {
    $user = $this->actingAsUser();
    $subject = createTicketSubject($user);

    $this->postJson('/api/profile/tickets', [
        'subject_id' => $subject->id,
        'message' => 'Need help with my order',
    ])->assertCreated()
        ->assertJsonPath('status', 1);

    expect(Ticket::where('user_id', $user->id)->count())->toBe(1)
        ->and(TicketMessage::where('user_id', $user->id)->count())->toBe(1);
});

it('rejects ticket create with invalid payload', function () {
    $this->actingAsUser();

    $this->postJson('/api/profile/tickets', [
        'subject_id' => 99999,
        'message' => 'ab',
    ])->assertStatus(422);
});

it('rate limits creating tickets within the cooldown window', function () {
    $user = $this->actingAsUser();
    $subject = createTicketSubject($user);

    Ticket::query()->create([
        'user_id' => $user->id,
        'subject_id' => $subject->id,
        'status' => Ticket::STATUS_ACTIVE,
        'created_at' => Carbon::now()->subMinute(),
    ]);

    $this->postJson('/api/profile/tickets', [
        'subject_id' => $subject->id,
        'message' => 'Another ticket too soon',
    ])->assertStatus(429)
        ->assertJsonPath('status', 0);
});

it('blocks new tickets when user already has three active ones', function () {
    $user = $this->actingAsUser();
    $subject = createTicketSubject($user);

    foreach (range(1, 3) as $i) {
        Ticket::query()->create([
            'user_id' => $user->id,
            'subject_id' => $subject->id,
            'status' => Ticket::STATUS_ACTIVE,
            'created_at' => Carbon::now()->subMinutes(10 + $i),
        ]);
    }

    $this->postJson('/api/profile/tickets', [
        'subject_id' => $subject->id,
        'message' => 'Fourth active ticket attempt',
    ])->assertStatus(422)
        ->assertJsonPath('status', 0);
});

it('allows owner to store a follow-up message after operator reply', function () {
    $user = $this->actingAsUser();
    $operator = User::factory()->create();
    $subject = createTicketSubject($operator);

    $ticket = Ticket::query()->create([
        'user_id' => $user->id,
        'subject_id' => $subject->id,
        'status' => Ticket::STATUS_ACTIVE,
    ]);

    TicketMessage::query()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $user->id,
        'message' => 'Initial user message',
        'status' => TicketMessage::PENDING,
    ]);

    TicketMessage::query()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $operator->id,
        'message' => 'Operator reply',
        'status' => TicketMessage::PENDING,
    ]);

    $this->postJson("/api/profile/tickets/{$ticket->id}/message", [
        'message' => 'Thanks for the help',
    ])->assertOk()
        ->assertJsonPath('status', 1);
});

it('rejects storing a message on another users ticket', function () {
    $owner = User::factory()->create();
    $subject = createTicketSubject($owner);
    $ticket = Ticket::query()->create([
        'user_id' => $owner->id,
        'subject_id' => $subject->id,
        'status' => Ticket::STATUS_ACTIVE,
    ]);

    TicketMessage::query()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $owner->id,
        'message' => 'Owner message',
        'status' => TicketMessage::PENDING,
    ]);

    $this->actingAsUser(User::factory()->create());

    $this->postJson("/api/profile/tickets/{$ticket->id}/message", [
        'message' => 'Attacker message',
    ])->assertUnauthorized()
        ->assertJsonPath('message', 'Unauthorized');
});

it('lists own tickets', function () {
    $user = $this->actingAsUser();
    $subject = createTicketSubject($user);
    Ticket::query()->create([
        'user_id' => $user->id,
        'subject_id' => $subject->id,
        'status' => Ticket::STATUS_ACTIVE,
    ]);

    $this->getJson('/api/profile/tickets')
        ->assertOk()
        ->assertJsonStructure(['data']);
});
