<?php

namespace Domain\Ticket\Repositories\Contracts;

use Application\Api\Ticket\Requests\TicketMessageRequest;
use Application\Api\Ticket\Requests\TicketRequest;
use Core\Http\Requests\TableRequest;
use Domain\Ticket\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface ITicketRepository.
 */
interface ITicketRepository
{
    /**
     * Get the tikets pagination.
     */
    public function index(TableRequest $request): LengthAwarePaginator;

    /**
     * Store the ticket.
     *
     * @throws \Exception
     */
    public function store(TicketRequest $request): JsonResponse;

    /**
     * Close the ticket
     */
    public function closeTicket(Ticket $ticket): JsonResponse;

    /**
     * Get the sport.
     */
    public function show(Ticket $ticket): Ticket;

    /**
     * Store the message of ticket.
     */
    public function storeMessage(TicketMessageRequest $request, Ticket $ticket): JsonResponse;
}
