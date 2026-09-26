<?php

namespace Domain\Product\Models;

use Database\Factories\OrderFactory;
use Domain\Payment\Models\Transaction;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property int $user_id
 * @property int|null $discount_id
 * @property string|null $description
 * @property int $product_count
 * @property numeric $total_amount
 * @property numeric $amount
 * @property numeric $discount_amount
 * @property numeric|null $delivery_amount
 * @property string $status
 * @property int $active
 * @property int|bool $vip
 * @property string|null $postal
 * @property string|null $address
 * @property string|null $postal_code
 * @property numeric $profit
 * @property numeric $profit_rate
 * @property numeric $exchange_rate
 * @property Carbon|null $expire_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Product> $products
 * @property-read User $user
 * @property-read Discount|null $discount
 * @property-read Collection<int, Transaction> $transactions
 * @property-read Collection<int, OrderLedger> $ledgers
 */
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    const PENDING = 'pending';

    const PAID = 'paid';

    const CANCELLED = 'cancelled';

    const SHIPPED = 'shipped';

    const DELIVERED = 'delivered';

    const RETURNED = 'returned';

    const REFUNDED = 'refunded';

    const FAILED = 'failed';

    const EXPIRED = 'expired';

    protected $guarded = [];

    protected $casts = [
        'active' => 'integer',
        'expire_date' => 'datetime',
    ];

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'order_product', 'order_id', 'product_id')
            ->withPivot('id', 'count', 'amount', 'status', 'color_id', 'size_id');
    }

    /**
     * @return HasMany<OrderLedger, $this>
     */
    public function ledgers(): HasMany
    {
        return $this->hasMany(OrderLedger::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Discount, $this>
     */
    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public static function generateCode(): string
    {
        do {
            $code = random_int(1111111111111111, 9999999999999999);
            $exists = self::query()
                ->where('code', $code)
                ->exists();
        } while ($exists);

        return (string) $code;
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'model_id', 'id')->where('model_type', Transaction::ORDER);
    }

    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }
}
