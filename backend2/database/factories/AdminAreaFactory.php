<?php

namespace Database\Factories;

use App\Models\AdminArea;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdminArea>
 */
class AdminAreaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name_ar' => 'حي '.fake()->unique()->numerify('####'),
            'name_en' => fake()->unique()->words(2, true),
            'code' => fake()->unique()->numerify('C-####'),
            'pcode' => null,
            'level' => 10,
            'parent_id' => null,
            'boundary' => $this->square(36.29, 33.51),
        ];
    }

    public function municipality(): static
    {
        return $this->state(['level' => 9]);
    }

    public function neighborhood(): static
    {
        return $this->state(['level' => 10]);
    }

    /** مضلع مربع مركزه (lon, lat) */
    public function at(float $lon, float $lat, float $half = 0.005): static
    {
        return $this->state(['boundary' => $this->square($lon, $lat, $half)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function square(float $lon, float $lat, float $half = 0.005): array
    {
        return [
            'type' => 'Polygon',
            'coordinates' => [[
                [$lon - $half, $lat - $half],
                [$lon + $half, $lat - $half],
                [$lon + $half, $lat + $half],
                [$lon - $half, $lat + $half],
                [$lon - $half, $lat - $half],
            ]],
        ];
    }
}
