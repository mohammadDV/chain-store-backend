<?php

namespace Application\Api\Ticket\Controllers;

use Application\Api\Ticket\Requests\TicketMessageRequest;
use Application\Api\Ticket\Requests\TicketRequest;
use Application\Api\Ticket\Requests\TicketStatusRequest;
use Core\Http\Controllers\Controller;
use Core\Http\Requests\TableRequest;
use Domain\Ticket\Models\Ticket;
use Domain\Ticket\Repositories\Contracts\ITicketRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class TicketController extends Controller
{
    /**
     * Constructor of TicketController.
     */
    public function __construct(protected  ITicketRepository $repository)
    {
        //
    }

    /**
     * Get all of tikets with pagination
     * @param TableRequest $request
     * @return JsonResponse
     */
    public function index(TableRequest $request): JsonResponse
    {
        return response()->json($this->repository->index($request), Response::HTTP_OK);
    }

    /**
     * Get the ticket.
     * @param
     * @return JsonResponse
     */
    public function show(Ticket $ticket) :JsonResponse
    {
        return response()->json($this->repository->show($ticket), Response::HTTP_OK);
    }

    /**
     * Store the ticket.
     * @param TicketRequest $request
     * @return JsonResponse
     */
    public function store(TicketRequest $request) :JsonResponse
    {
        return $this->repository->store($request);
    }

    /**
     * Close the ticket
     * @param Ticket $ticket
     * @return JsonResponse
     */
    public function closeTicket(Ticket $ticket) :JsonResponse
    {
        return $this->repository->closeTicket($ticket);
    }

     /**
     * Store the message of ticket.
     * @param TicketMessageRequest $request
     * @param Ticket $ticket
     * @return JsonResponse
     * @throws \Exception
     */
    public function storeMessage(TicketMessageRequest $request, Ticket $ticket) :JsonResponse
    {
        return $this->repository->storeMessage($request, $ticket);
    }
}