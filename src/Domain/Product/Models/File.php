<?php

namespace Domain\Product\Models;

use Database\Factories\FileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    /** @use HasFactory<FileFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
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

    protected static function newFactory(): FileFactory
    {
        return FileFactory::new();
    }
}
