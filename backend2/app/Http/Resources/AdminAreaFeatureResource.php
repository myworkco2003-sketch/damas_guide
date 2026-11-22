<?php

namespace App\Http\Resources;

use App\Models\AdminArea;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Feature واحدة بصيغة GeoJSON.
 *
 * @mixin AdminArea
 */
class AdminAreaFeatureResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'type' => 'Feature',
            'id' => $this->id,
            'properties' => [
                'id' => $this->id,
                'name_ar' => $this->name_ar,
                'name_en' => $this->name_en,
                'code' => $this->code,
                'level' => $this->level,
                'level_name' => $this->level_name,
                'parent_id' => $this->parent_id,
                'parent_name_ar' => $this->parent?->name_ar ?? '',
            ],
            'geometry' => $this->boundary,
        ];
    }
}
