<?php

namespace Domain\Product\Models;

use Database\Factories\CategoryFactory;
use Domain\Brand\Models\Brand;
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
 * @property string $title
 * @property int $status
 * @property string|null $description
 * @property string|null $image
 * @property int $parent_id
 * @property int $priority
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Brand> $brands
 * @property-read User|null $user
 * @property-read Collection<int, Product> $products
 * @property-read Category|null $parent
 * @property-read Collection<int, Category> $children
 * @property-read Collection<int, Category> $childrenRecursive
 * @property-read Collection<int, Category> $allChildren
 * @property-read Category|null $parentRecursive
 */
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $guarded = [];

    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }

    /**
     * Get the brands that belong to the category.
     *
     * @return BelongsToMany<Brand, $this>
     */
    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'brand_category', 'category_id', 'brand_id')->withPivot('priority', 'status');
    }

    /**
     * Get the user that owns the category.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the products that belong to the category.
     *
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'category_product', 'category_id', 'product_id');
    }

    /**
     * Get the parent category.
     *
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get the children categories.
     *
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->where('status', 1);
    }

    /**
     * Get all children recursively
     *
     * @return HasMany<Category, $this>
     */
    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }

    /**
     * Get all descendants (all levels of children)
     *
     * @return HasMany<Category, $this>
     */
    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }

    /**
     * Get all parent categories up to the root
     *
     * @return BelongsTo<Category, $this>
     */
    public function parentRecursive(): BelongsTo
    {
        return $this->parent()->with('parentRecursive');
    }

    /**
     * Get the full category path (breadcrumb)
     *
     * @return list<array{id: int, title: string}>
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
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Check if this category is a root category
     */
    public function isRoot(): bool
    {
        return (int) $this->parent_id === 0;
    }

    /**
     * Get the depth level of this category
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
