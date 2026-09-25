<?php

namespace Domain\Ticket\Models;

use Database\Factories\TicketFactory;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $subject_id
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read TicketSubject $subject
 * @property-read Collection<int, TicketMessage> $messages
 * @property-read TicketMessage|null $message
 */
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    protected $guarded = [];

    const STATUS_ACTIVE = 'active';

    const STATUS_CLOSED = 'closed';

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<TicketSubject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(TicketSubject::class, 'subject_id', 'id');
    }

    /**
     * @return HasMany<TicketMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }

    /**
     * Get the first message of the chat
     *
     * @return HasOne<TicketMessage, $this>
     */
    public function message(): HasOne
    {
        return $this->hasOne(TicketMessage::class);
    }
}
