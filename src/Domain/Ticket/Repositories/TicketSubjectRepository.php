<?php

namespace Domain\Ticket\Repositories;

use Application\Api\Ticket\Requests\SubjectRequest;
use Core\Http\Requests\TableRequest;
use Core\Http\traits\GlobalFunc;
use Domain\Ticket\Models\TicketSubject;
use Domain\Ticket\Repositories\Contracts\ITicketSubjectRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class TicketSubjectRepository implements ITicketSubjectRepository {

    use GlobalFunc;

    /**
     * Get the Subjects.
     * @return Collection
     */
    public function activeSubjects() :Collection
    {
        return TicketSubject::query()
            ->where('status', 1)
            ->get();
    }

    /**
     * Get the subject.
     * @param TicketSubject $subject
     * @return TicketSubject
     */
    public function show(TicketSubject $subject) :TicketSubject
    {
        return $subject;
    }
}
