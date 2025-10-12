<?php

namespace Domain\Ticket\Models;

use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketMessage extends Model
{
    /** @use HasFactory<\Database\Factories\TicketMessageFactory> */
    use HasFactory;

    const READ = 'read';
    const PENDING = 'pending';

    protected $guarded = [];

    public function ticket() {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}