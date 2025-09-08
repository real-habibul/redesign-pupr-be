<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\InformasiUmum;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InformasiUmum>
 */
class InformasiUmumFactory extends Factory
{
    protected $model = InformasiUmum::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'jenis_informasi' => 'manual',
            'nama_paket' => 'Paket ' . $this->faker->words(3, true) . ' - ' . $this->faker->city(),
            'nama_ppk' => $this->faker->name(),
            'jabatan_ppk' => $this->faker->randomElement(['Kepala Bidang', 'Kepala Seksi', 'Pejabat Pembuat Komitmen']),
            'nama_balai' => 'Balai ' . $this->faker->words(2, true),
            'tipologi' => $this->faker->randomElement(['A', 'B', 'C', 'D']),
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'updated_at' => now(),
        ];
    }

    /**
     * Configure for Jawa Tengah region
     */
    public function jateng()
    {
        return $this->state(function (array $attributes) {
            return [
                'nama_balai' => $this->faker->randomElement([
                    'Balai Besar Pelaksanaan Jalan Nasional VII Semarang',
                    'Balai Pelaksanaan Jalan Nasional Magelang',
                    'Balai Pelaksanaan Jalan Nasional Surakarta',
                    'Balai Wilayah Sungai Pemali Juana',
                    'Balai Wilayah Sungai Serayu Opak'
                ]),
                'nama_paket' => 'Paket Perencanaan ' . $this->faker->randomElement([
                    'Jalan Tol Semarang-Demak',
                    'Jembatan Suramadu Jawa Tengah',
                    'Pembangunan Infrastruktur Solo',
                    'Renovasi Jalan Raya Yogya-Solo',
                    'Proyek Bandara Ahmad Yani'
                ]),
            ];
        });
    }
}
