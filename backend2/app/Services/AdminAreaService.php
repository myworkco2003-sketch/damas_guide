<?php

namespace App\Services;

use App\Models\AdminArea;
use App\Support\Geometry;
use Illuminate\Database\Eloquent\Collection;

class AdminAreaService
{
    /**
     * التقسيمات الإدارية مرتبة level ثم id (نفس Meta.ordering بـ Django).
     *
     * @return Collection<int, AdminArea>
     */
    public function list(?int $level = null, ?int $parentId = null): Collection
    {
        return AdminArea::query()
            ->with('parent:id,name_ar')
            ->when($level !== null, fn ($q) => $q->where('level', $level))
            ->when($parentId !== null, fn ($q) => $q->where('parent_id', $parentId))
            ->orderBy('level')
            ->orderBy('id')
            ->get();
    }

    /**
     * الحي (مستوى 10) أولاً، وإذا ما لقينا نرجع للبلدية (مستوى 9).
     * النقطة خارج حدود دمشق بترجع null بدون استعلام.
     */
    public function findByPoint(float $lat, float $lon): ?AdminArea
    {
        if (! $this->isInsideDamascus($lat, $lon)) {
            return null;
        }

        foreach ([10, 9] as $level) {
            $areas = AdminArea::query()
                ->where('level', $level)
                ->orderBy('id')
                ->get(['id', 'name_ar', 'boundary']);

            foreach ($areas as $area) {
                if (Geometry::pointInGeometry($lon, $lat, $area->boundary)) {
                    return $area;
                }
            }
        }

        return null;
    }

    public function isInsideDamascus(float $lat, float $lon): bool
    {
        $b = config('damascus.bounds');

        return $lat >= $b['lat_min'] && $lat <= $b['lat_max']
            && $lon >= $b['lon_min'] && $lon <= $b['lon_max'];
    }
}
