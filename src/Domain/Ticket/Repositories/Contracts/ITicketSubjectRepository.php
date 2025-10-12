<?php

namespace Domain\Ticket\Repositories\Contracts;

use Core\Http\Requests\TableRequest;
use Domain\Ticket\Models\TicketSubject;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

 /**
 * Interface ITicketSubjectRepository.
 */
interface ITicketSubjectRepository  {

    /**
     * Get the Subjects.
     * @return Collection
     */
    public function activeSubjects() :Collection;

    /**
     * Get the subject.
     * @param TicketSubject $subject
     * @return TicketSubject
     */
    public function show(TicketSubject $subject) :TicketSubject;

}