<?php

namespace App\Models;

use App\Support\SearchIndex;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminArea extends Model
{
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
        static::saving(function (AdminArea $area) {
            $parentName = $area->parent?->name_ar ?? '';
            $area->search_text = SearchIndex::build(
                $area->name_ar,
                $area->name_en,
                $parentName,
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

    // نفس level_name property: $area->level_name
    public function getLevelNameAttribute(): string
    {
        return match ($this->level) {
            8 => 'قطاع',
            9 => 'بلدية',
            10 => 'حي',
            default => 'محافظة',
        };
    }
}