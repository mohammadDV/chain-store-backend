<?php

namespace Domain\Product\Models;

use Database\Factories\StockFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    /** @use HasFactory<StockFactory> */
    use HasFactory;

    protected $fillable = [
        'size_id',
        'reserved',
        'quantity',
    ];

    protected $casts = [
        'reserved' => 'integer',
        'quantity' => 'integer',
    ];

    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }

    protected static function newFactory(): StockFactory
    {
        return StockFactory::new();
    }
}
