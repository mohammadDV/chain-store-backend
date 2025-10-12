<?php

namespace Domain\Ticket\Repositories\Contracts;

use Application\Api\Ticket\Requests\TicketMessageRequest;
use Application\Api\Ticket\Requests\TicketRequest;
use Application\Api\Ticket\Requests\TicketStatusRequest;
use Core\Http\Requests\TableRequest;
use Domain\Ticket\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

 /**
 * Interface ITicketRepository.
 */
interface ITicketRepository  {

    /**
     * Get the tikets pagination.
     * @param TableRequest $request
     * @return LengthAwarePaginator
     */
    public function index(TableRequest $request) :LengthAwarePaginator;

    /**
     * Store the ticket.
     * @param TicketRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function store(TicketRequest $request) :JsonResponse;

    /**
     * Close the ticket
     * @param Ticket $ticket
     * @return JsonResponse
     */
    public function closeTicket(Ticket $ticket) :JsonResponse;

    /**
     * Get the sport.
     * @param Ticket $ticket
     * @return Ticket
     */
    public function show(Ticket $ticket) :Ticket;

    /**
     * Store the message of ticket.
     * @param TicketMessageRequest $request
     * @param Ticket $ticket
     * @return JsonResponse
     */
    public function storeMessage(TicketMessageRequest $request, Ticket $ticket) :JsonResponse;

}