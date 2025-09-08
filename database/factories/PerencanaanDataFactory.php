<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\PerencanaanData;
use App\Models\InformasiUmum;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PerencanaanData>
 */
class PerencanaanDataFactory extends Factory
{
    protected $model = PerencanaanData::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'informasi_umum_id' => 1, // Will be set by seeder
            'identifikasi_kebutuhan_id' => $this->faker->numberBetween(1, 100),
            'shortlist_vendor_id' => $this->faker->numberBetween(1, 50),
            'team_teknis_balai_id' => $this->faker->numberBetween(1, 10),
            'pengawas_id' => json_encode([$this->faker->numberBetween(1, 10)]),
            'petugas_lapangan_id' => json_encode([$this->faker->numberBetween(1, 20)]),
            'pengolah_data_id' => json_encode([$this->faker->numberBetween(1, 15)]),
            'doc_berita_acara' => $this->faker->optional()->url(),
            'doc_berita_acara_validasi' => $this->faker->optional()->url(),
            'region_code' => 'jateng', // Will be overridden by seeder
            'period_year' => $this->faker->numberBetween(2024, 2025),
            'city_code' => $this->faker->randomElement(['3301', '3302', '3303', '3304', '3305']),
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'updated_at' => now(),
        ];
    }

    /**
     * Configure the factory for a specific region
     */
    public function forRegion(string $regionCode)
    {
        return $this->state(function (array $attributes) use ($regionCode) {
            return [
                'region_code' => $regionCode,
            ];
        });
    }

    /**
     * Configure the factory for a specific period
     */
    public function forPeriod(int $year)
    {
        return $this->state(function (array $attributes) use ($year) {
            return [
                'period_year' => $year,
            ];
        });
    }
}
