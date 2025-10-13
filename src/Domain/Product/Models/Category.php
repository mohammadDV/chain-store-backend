<?php

namespace Domain\Product\Models;

use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->where('status', 1);
    }

    /**
     * Get all children recursively
     */
    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }

    /**
     * Get all descendants (all levels of children)
     */
    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    /**
     * Get all parent categories up to the root
     */
    public function parentRecursive()
    {
        return $this->parent()->with('parentRecursive');
    }

    /**
     * Get the full category path (breadcrumb)
     * @return array
     */
    public function getPath(): array
    {
        $path = [];
        $category = $this;

        while ($category) {
            array_unshift($path, [
                'id' => $category->id,
                'title' => $category->title,
            ]);
            $category = $category->parent;
        }

        return $path;
    }

    /**
     * Check if this category has children
     * @return bool
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Check if this category is a root category
     * @return bool
     */
    public function isRoot(): bool
    {
        return $this->parent_id === 0 || $this->parent_id === null;
    }

    /**
     * Get the depth level of this category
     * @return int
     */
    public function getDepth(): int
    {
        $depth = 0;
        $category = $this;

        while ($category->parent_id !== 0 && $category->parent) {
            $depth++;
            $category = $category->parent;
        }

        return $depth;
    }
}