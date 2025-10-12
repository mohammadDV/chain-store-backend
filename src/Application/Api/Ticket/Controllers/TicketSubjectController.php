<?php

namespace Application\Api\Ticket\Controllers;

use Core\Http\Controllers\Controller;
use Core\Http\Requests\TableRequest;
use Domain\Ticket\Models\TicketSubject;
use Domain\Ticket\Repositories\Contracts\ITicketSubjectRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class TicketSubjectController extends Controller
{
    /**
     * Constructor of TicketSubjectController.
     */
    public function __construct(protected  ITicketSubjectRepository $repository)
    {
        //
    }

    /**
     * Get all of Subjects
     * @return JsonResponse
     */
    public function activeSubjects(TableRequest $request): JsonResponse
    {
        return response()->json($this->repository->activeSubjects($request), Response::HTTP_OK);
    }

    /**
     * Get the subject.
     * @param TicketSubject $subject
     * @return JsonResponse
     */
    public function show(TicketSubject $ticketSubject) :JsonResponse
    {
        return response()->json($this->repository->show($ticketSubject), Response::HTTP_OK);
    }
}