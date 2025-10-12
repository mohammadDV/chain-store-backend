<?php

namespace Domain\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    /** @use HasFactory<\Database\Factories\FileFactory> */
    use HasFactory;

    protected $fillable = [
        'path',
        'type',
        'status',
        'priority',
    ];

    protected $casts = [
        'status' => 'integer',
        'priority' => 'integer',
    ];

    /**
     * Get the product that owns the file.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
