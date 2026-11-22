<?php

namespace App\Models;

use App\Support\Geometry;
use App\Support\SearchIndex;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminArea extends Model
{
    use HasFactory;

    protected $table = 'admin_areas';

    protected $fillable = [
        'name_ar',
        'name_en',
        'code',
        'pcode',
        'level',
        'parent_id',
        'boundary',
        'search_text',
    ];

    protected $casts = [
        'boundary' => 'array',
        'level' => 'integer',
    ];

    protected static function booted(): void
    {
        // نفس save() بـ Django: بيحسب search_text قبل كل حفظ
        static::saving(function (AdminArea $area) {
            $area->search_text = SearchIndex::build(
                $area->name_ar,
                $area->name_en,
                $area->parent?->name_ar ?? '',
                $area->code
            );
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(AdminArea::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(AdminArea::class, 'parent_id');
    }

    // $area->level_name
    public function getLevelNameAttribute(): string
    {
        return match ($this->level) {
            8 => 'قطاع',
            9 => 'بلدية',
            10 => 'حي',
            default => 'محافظة',
        };
    }

    /**
     * فلتر البحث (q_admin بـ search_all): search_text لكل المشتقات + الاسم الإنجليزي + الرمز.
     * الاستخدام: AdminArea::matching($q, SearchIndex::variants($q))
     */
    public function scopeMatching(Builder $query, string $term, array $variants): Builder
    {
        $like = fn (string $s) => '%'.addcslashes($s, '\\%_').'%';

        return $query->where(function (Builder $w) use ($term, $variants, $like) {
            foreach ($variants as $v) {
                $w->orWhere('search_text', 'ilike', $like($v));
            }
            $w->orWhere('name_en', 'ilike', $like($term))
                ->orWhere('code', 'ilike', $like($term));
        });
    }

    /** مركز المضلع [lon, lat] لنتائج البحث */
    public function centroid(): array
    {
        return Geometry::centroid($this->boundary);
    }
}
