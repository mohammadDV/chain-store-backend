<?php

namespace Domain\Product\Models;

use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $code
 * @property string $status
 * @property int $product_count
 * @property numeric $amount
 * @property numeric $delivery_amount
 * @property numeric $total_amount
 * @property numeric $profit
 * @property numeric $profit_rate
 * @property numeric $exchange_rate
 * @property int $active
 * @property int|bool $vip
 * @property Carbon|null $expire_date
 * @property string $product_name
 * @property string|null $brand
 * @property string|null $description
 * @property string|null $image
 * @property string|null $fullname
 * @property string|null $mobile
 * @property string|null $email
 * @property string|null $address
 * @property string|null $postal_code
 * @property string|null $payment_receipt
 * @property string|null $refund_receipt
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 */
class ManualOrder extends Model
{
    public const PENDING = 'pending';

    public const PAID = 'paid';

    public const CANCELLED = 'cancelled';

    public const SHIPPED = 'shipped';

    public const DELIVERED = 'delivered';

    public const RETURNED = 'returned';

    public const REFUNDED = 'refunded';

    public const FAILED = 'failed';

    public const EXPIRED = 'expired';

    protected $guarded = [];

    protected $casts = [
        'active' => 'integer',
        'vip' => 'integer',
        'expire_date' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generateCode(): string
    {
        do {
            $code = (string) random_int(1111111111111111, 9999999999999999);
            $exists = self::query()
                ->where('code', $code)
                ->exists();
        } while ($exists);

        return $code;
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::PENDING => __('site.pending'),
            self::PAID => __('site.paid'),
            self::CANCELLED => __('site.cancelled'),
            self::SHIPPED => __('site.shipped'),
            self::DELIVERED => __('site.delivered'),
            self::RETURNED => __('site.returned'),
            self::REFUNDED => __('site.refunded'),
            self::FAILED => __('site.failed'),
            self::EXPIRED => __('site.expired'),
        ];
    }
}
