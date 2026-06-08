<?php

namespace Cesa\FormTransfer\Models;

use Cesa\FormTransfer\Database\Factories\FormTransferPublicCategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Webkul\Security\Traits\HasNullableCreator;

class FormTransferPublicCategory extends Model
{
    use HasFactory, HasNullableCreator, SoftDeletes;

    public const SLUG_TRANSFER_REQUESTS = 'transfer-requests';

    public const SLUG_AFFILIATES = 'afiliasi';

    protected $table = 'form_transfer_public_categories';

    protected $fillable = [
        'creator_id',
        'name',
        'slug',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active'  => 'boolean',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function reservedSlugs(): array
    {
        return [
            'admin',
            'api',
            'build',
            'css',
            'exit-clearance',
            'favicon',
            'files',
            'fonts',
            'images',
            'js',
            'lead',
            'livewire',
            'login',
            'man-power',
            'padelnis',
            'storage',
            'up',
            'vendor',
        ];
    }

    public static function normalizeSlug(mixed $slug): string
    {
        return Str::of((string) $slug)
            ->trim()
            ->lower()
            ->replace('_', '-')
            ->slug('-')
            ->toString();
    }

    public static function isAllowedSlug(mixed $slug): bool
    {
        $slug = self::normalizeSlug($slug);

        return $slug !== '' && ! in_array($slug, self::reservedSlugs(), true);
    }

    public static function isBuiltInSlug(string $slug): bool
    {
        return in_array(self::normalizeSlug($slug), [
            self::SLUG_TRANSFER_REQUESTS,
            self::SLUG_AFFILIATES,
        ], true);
    }

    public static function activeSlugExists(string $slug): bool
    {
        return self::query()
            ->active()
            ->where('slug', self::normalizeSlug($slug))
            ->exists();
    }

    public function isBuiltIn(): bool
    {
        return self::isBuiltInSlug((string) $this->slug)
            || self::isBuiltInSlug((string) $this->getOriginal('slug'));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function formTransfers(): BelongsToMany
    {
        return $this->belongsToMany(
            FormTransfer::class,
            'form_transfer_public_category_assignments',
            'form_transfer_public_category_id',
            'form_transfer_id',
        )->withTimestamps();
    }

    protected static function booted(): void
    {
        static::saving(function (self $category): void {
            $category->slug = self::normalizeSlug($category->slug ?: $category->name);
            $originalSlug = self::normalizeSlug($category->getOriginal('slug'));

            if (! self::isAllowedSlug($category->slug)) {
                throw ValidationException::withMessages([
                    'slug' => __('form-transfer::filament/clusters/configurations/resources/public-category.validation.slug'),
                ]);
            }

            if ($category->exists && self::isBuiltInSlug($originalSlug) && $category->slug !== $originalSlug) {
                throw ValidationException::withMessages([
                    'slug' => __('form-transfer::filament/clusters/configurations/resources/public-category.validation.built_in_slug'),
                ]);
            }

            if (self::isBuiltInSlug($category->slug) && ! (bool) $category->is_active) {
                throw ValidationException::withMessages([
                    'is_active' => __('form-transfer::filament/clusters/configurations/resources/public-category.validation.built_in_active'),
                ]);
            }

            if (! $category->creator_id && Auth::check()) {
                $category->creator_id = Auth::id();
            }
        });

        static::deleting(function (self $category): void {
            if ($category->isBuiltIn()) {
                throw ValidationException::withMessages([
                    'slug' => __('form-transfer::filament/clusters/configurations/resources/public-category.validation.built_in_delete'),
                ]);
            }
        });
    }

    protected static function newFactory(): Factory
    {
        return FormTransferPublicCategoryFactory::new();
    }
}
