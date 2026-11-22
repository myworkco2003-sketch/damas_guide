<?php

use App\Models\AdminArea;

describe('GET /api/admin-areas', function () {
    it('returns a GeoJSON FeatureCollection ordered by level then id', function () {
        $municipality = AdminArea::factory()->municipality()->create(['name_ar' => 'بلدية المزة']);
        AdminArea::factory()->neighborhood()->for($municipality, 'parent')->create();

        $this->getJson('/api/admin-areas')
            ->assertOk()
            ->assertJsonPath('type', 'FeatureCollection')
            ->assertJsonPath('count', 2)
            ->assertJsonPath('features.0.properties.level_name', 'بلدية')
            ->assertJsonPath('features.1.properties.parent_name_ar', 'بلدية المزة')
            ->assertJsonPath('features.1.geometry.type', 'Polygon');
    });

    it('filters by level and ignores invalid values like Django', function () {
        AdminArea::factory()->municipality()->create();
        AdminArea::factory()->neighborhood()->count(2)->create();

        $this->getJson('/api/admin-areas?level=10')->assertJsonPath('count', 2);
        $this->getJson('/api/admin-areas?level=abc')->assertJsonPath('count', 3);
    });
});

describe('GET /api/admin-areas/lookup', function () {
    it('returns the neighborhood containing the point', function () {
        $area = AdminArea::factory()->neighborhood()->at(36.29, 33.51)->create();

        $this->getJson('/api/admin-areas/lookup?lat=33.51&lon=36.29')
            ->assertOk()
            ->assertExactJson(['admin_area' => ['id' => $area->id, 'name_ar' => $area->name_ar]]);
    });

    it('falls back to the municipality when no neighborhood matches', function () {
        $municipality = AdminArea::factory()->municipality()->at(36.29, 33.51, 0.02)->create();
        AdminArea::factory()->neighborhood()->at(36.40, 33.60)->create();

        $this->getJson('/api/admin-areas/lookup?lat=33.51&lon=36.29')
            ->assertExactJson(['admin_area' => ['id' => $municipality->id, 'name_ar' => $municipality->name_ar]]);
    });

    it('returns null outside Damascus bounds', function () {
        $this->getJson('/api/admin-areas/lookup?lat=10&lon=10')
            ->assertOk()
            ->assertExactJson(['admin_area' => null]);
    });

    it('returns 400 when lat or lon is missing or not numeric', function () {
        $this->getJson('/api/admin-areas/lookup?lat=abc&lon=36.29')
            ->assertStatus(400)
            ->assertExactJson(['detail' => 'lat و lon مطلوبان ويجب أن يكونا أرقامًا.']);

        $this->getJson('/api/admin-areas/lookup')->assertStatus(400);
    });
});
