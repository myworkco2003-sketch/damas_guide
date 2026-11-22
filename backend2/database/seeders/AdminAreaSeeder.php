<?php

namespace Database\Seeders;

use App\Models\AdminArea;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminAreaSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/damascus_admin_areas.geojson');

        if (! is_file($path)) {
            $this->command->error("GeoJSON file not found: {$path}");

            return;
        }

        // الأعلى مستوى أولاً حتى يكون الأب موجود قبل الابن (FK)
        $features = collect(json_decode(file_get_contents($path), true)['features'] ?? [])
            ->sortBy(fn ($f) => $f['properties']['level'])
            ->values();

        $this->command->info("Found {$features->count()} features in GeoJSON. Beginning import...");

        DB::transaction(function () use ($features) {
            // unguarded لأن id مو بالـ $fillable ونحن بدنا نحافظ على نفس الـ ids القديمة
            Model::unguarded(function () use ($features) {
                foreach ($features as $feature) {
                    $p = $feature['properties'];

                    AdminArea::updateOrCreate(
                        ['id' => $p['id']],
                        [
                            'level' => $p['level'],
                            'parent_id' => $p['parent_id'] ?? null,
                            'name_ar' => $p['name_ar'],
                            'name_en' => $p['name_en'] ?? '',
                            'code' => $p['code'] ?? '',
                            'pcode' => $p['pcode'] ?? null,
                            'boundary' => $feature['geometry'],
                        ]
                    );
                }
            });

            // لأننا أدخلنا ids يدوياً، لازم نحرّك الـ sequence وإلا أي إدخال جديد رح يتعارض
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("SELECT setval(pg_get_serial_sequence('admin_areas', 'id'), (SELECT COALESCE(MAX(id), 1) FROM admin_areas))");
            }
        });

        $this->command->info("Imported {$features->count()} administrative areas");
    }
}
