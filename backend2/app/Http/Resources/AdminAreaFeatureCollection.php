<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * FeatureCollection بصيغة GeoJSON.
 */
class AdminAreaFeatureCollection extends ResourceCollection
{
    public static $wrap = null;

    public $collects = AdminAreaFeatureResource::class;

    public function toArray(Request $request): array
    {
        return [
            'type' => 'FeatureCollection',
            'count' => $this->collection->count(),
            'features' => $this->collection,
        ];
    }
}
