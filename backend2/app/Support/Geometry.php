<?php

namespace App\Support;

class Geometry
{
    /** مركز دمشق التقريبي [lon, lat] كقيمة افتراضية */
    public const DEFAULT_CENTER = [36.295, 33.513];

    public static function pointInGeometry(float $lon, float $lat, mixed $geometry): bool
    {
        if (! is_array($geometry)) {
            return false;
        }

        $coords = $geometry['coordinates'] ?? [];
        $type = $geometry['type'] ?? null;

        if ($type === 'Polygon') {
            return self::inPolygon($lon, $lat, $coords);
        }

        if ($type === 'MultiPolygon') {
            foreach ($coords as $polygon) {
                if (self::inPolygon($lon, $lat, $polygon)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** polygon = [outer_ring, hole_1, hole_2, ...] */
    private static function inPolygon(float $lon, float $lat, array $polygon): bool
    {
        if (empty($polygon) || ! self::inRing($lon, $lat, $polygon[0])) {
            return false;
        }

        foreach (array_slice($polygon, 1) as $hole) {
            if (self::inRing($lon, $lat, $hole)) {
                return false;
            }
        }

        return true;
    }

    private static function inRing(float $lon, float $lat, array $ring): bool
    {
        $inside = false;
        $j = count($ring) - 1;

        for ($i = 0, $n = count($ring); $i < $n; $i++) {
            [$xi, $yi] = $ring[$i];
            [$xj, $yj] = $ring[$j];

            if (($yi > $lat) !== ($yj > $lat) && $lon < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi) {
                $inside = ! $inside;
            }

            $j = $i;
        }

        return $inside;
    }

    /**
     * متوسط نقاط الحلقة الخارجية الأولى، نفس منطق search_all القديم.
     *
     * @return array{0: float, 1: float} [lon, lat]
     */
    public static function centroid(mixed $boundary): array
    {
        if (! is_array($boundary) || empty($boundary['coordinates'])) {
            return self::DEFAULT_CENTER;
        }

        try {
            $poly = $boundary['coordinates'][0];

            // MultiPolygon: ناخد أول حلقة من أول مضلع
            if (is_array($poly[0] ?? null) && is_array($poly[0][0] ?? null)) {
                $poly = $poly[0];
            }

            $count = count($poly);
            if ($count === 0) {
                return self::DEFAULT_CENTER;
            }

            $sumLon = 0.0;
            $sumLat = 0.0;
            foreach ($poly as $pt) {
                $sumLon += $pt[0];
                $sumLat += $pt[1];
            }

            return [$sumLon / $count, $sumLat / $count];
        } catch (\Throwable) {
            return self::DEFAULT_CENTER;
        }
    }
}
