<?php

namespace Domain\Ticket\Repositories\Contracts;

use Domain\Ticket\Models\TicketSubject;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface ITicketSubjectRepository.
 */
interface ITicketSubjectRepository
{
    /**
     * Get the Subjects.
     */
    public function activeSubjects(): Collection;

    /**
     * Get the subject.
     */
    public function show(TicketSubject $subject): TicketSubject;
}
