<?php

namespace Domain\Ticket\Models;

use Database\Factories\TicketFactory;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    protected $guarded = [];

    const STATUS_ACTIVE = 'active';

    const STATUS_CLOSED = 'closed';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->belongsTo(TicketSubject::class, 'subject_id', 'id');
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class);
    }

    /**
     * Get the first message of the chat
     */
    public function message(): HasOne
    {
        return $this->hasOne(TicketMessage::class);
    }
}
