<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexAdminAreaRequest;
use App\Http\Requests\LookupAdminAreaRequest;
use App\Http\Resources\AdminAreaFeatureCollection;
use App\Services\AdminAreaService;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;

#[Group('Admin Areas', description: 'التقسيمات الإدارية (قطاع، بلدية، حي)')]
class AdminAreaController extends Controller
{
    public function __construct(private readonly AdminAreaService $service) {}

    /**
     * قائمة التقسيمات الإدارية
     *
     * بترجع FeatureCollection بصيغة GeoJSON. (views.list_admin_areas_geojson)
     */
    public function index(IndexAdminAreaRequest $request): AdminAreaFeatureCollection
    {
        return new AdminAreaFeatureCollection(
            $this->service->list($request->level(), $request->parentId())
        );
    }

    /**
     * تحديد الحي أو البلدية من إحداثيات
     *
     * بترجع admin_area = null إذا النقطة خارج حدود دمشق أو ما وقعت بأي منطقة. (geo_lookup.lookup_admin_area)
     */
    #[Response(400, 'lat أو lon مفقود أو غير رقمي', type: 'array{detail: string}')]
    public function lookup(LookupAdminAreaRequest $request): JsonResponse
    {
        $area = $this->service->findByPoint($request->lat(), $request->lon());

        return response()->json([
            'admin_area' => $area ? ['id' => $area->id, 'name_ar' => $area->name_ar] : null,
        ]);
    }
}
