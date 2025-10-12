<?php

namespace Domain\Ticket\Models;

use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketSubject extends Model
{
    /** @use HasFactory<\Database\Factories\TicketSubjectFactory> */
    use HasFactory;
    protected $guarded = [];

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function tickets() {
        return $this->hasMany(Ticket::class, 'subject_id', 'id');
    }
}