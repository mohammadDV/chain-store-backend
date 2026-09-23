<?php

namespace Domain\Ticket\Repositories;

use Core\Http\traits\GlobalFunc;
use Domain\Ticket\Models\TicketSubject;
use Domain\Ticket\Repositories\Contracts\ITicketSubjectRepository;
use Illuminate\Database\Eloquent\Collection;

class TicketSubjectRepository implements ITicketSubjectRepository
{
    use GlobalFunc;

    /**
     * Get the Subjects.
     */
    public function activeSubjects(): Collection
    {
        return TicketSubject::query()
            ->where('status', 1)
            ->get();
    }

    /**
     * Get the subject.
     */
    public function show(TicketSubject $subject): TicketSubject
    {
        return $subject;
    }
}
