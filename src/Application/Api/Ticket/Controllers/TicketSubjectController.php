<?php

namespace Application\Api\Ticket\Controllers;

use Core\Http\Controllers\Controller;
use Domain\Ticket\Models\TicketSubject;
use Domain\Ticket\Repositories\Contracts\ITicketSubjectRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class TicketSubjectController extends Controller
{
    /**
     * Constructor of TicketSubjectController.
     */
    public function __construct(protected ITicketSubjectRepository $repository)
    {
        //
    }

    /**
     * Get all of Subjects
     */
    public function activeSubjects(): JsonResponse
    {
        return response()->json($this->repository->activeSubjects(), Response::HTTP_OK);
    }

    /**
     * Get the subject.
     */
    public function show(TicketSubject $ticketSubject): JsonResponse
    {
        return response()->json($this->repository->show($ticketSubject), Response::HTTP_OK);
    }
}
