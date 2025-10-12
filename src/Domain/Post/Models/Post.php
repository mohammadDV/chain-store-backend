<?php

namespace Domain\Post\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Database\Factories\PostFactory;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    /** @use HasFactory<\Database\Factories\PostFactory> */
    use HasFactory, Sluggable;

    protected $guarded = [];

    public function sluggable() : array
    {
        return [
          'slug' => [
              'source' => 'title'
          ]
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeNameAttribute()
    {
        return __('site.' . Config('custom.POST_TYPE')[$this->type]);
    }

    public function getStatusNameAttribute()
    {
        return $this->status == 1 ? __('site.Active') : __('site.Inactive');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return PostFactory::new();
    }
}
