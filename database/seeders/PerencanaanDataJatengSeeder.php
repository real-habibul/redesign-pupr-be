<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PerencanaanData;
use App\Models\InformasiUmum;
use App\Models\Settings;

class PerencanaanDataJatengSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Set organization settings for Jawa Tengah
        Settings::set('org_name', 'E-Katalog SIPASTI Jawa Tengah', 'string', 'Organization name');
        Settings::set('org_type', 'provinsi', 'string', 'Organization type');
        Settings::set('org_region_code', 'jateng', 'string', 'Organization region code');
        Settings::set('org_logo_url', '/storage/branding/jateng.png', 'string', 'Organization logo URL');

        // Create sample InformasiUmum records for Jawa Tengah
        $informasiUmumIds = [];

        // Create 5 InformasiUmum records specifically for Jawa Tengah
        for ($i = 1; $i <= 5; $i++) {
            $informasiUmum = InformasiUmum::factory()->jateng()->create();
            $informasiUmumIds[] = $informasiUmum->id;
        }

        // Create PerencanaanData records for each InformasiUmum
        foreach ($informasiUmumIds as $informasiUmumId) {
            PerencanaanData::factory()
                ->forRegion('jateng')
                ->forPeriod(2025)
                ->create([
                    'informasi_umum_id' => $informasiUmumId,
                ]);
        }

        // Create some additional records for different periods
        $additionalInfoIds = [];
        for ($i = 1; $i <= 3; $i++) {
            $informasiUmum = InformasiUmum::factory()->jateng()->create();
            $additionalInfoIds[] = $informasiUmum->id;
        }

        foreach ($additionalInfoIds as $informasiUmumId) {
            PerencanaanData::factory()
                ->forRegion('jateng')
                ->forPeriod(2024)
                ->create([
                    'informasi_umum_id' => $informasiUmumId,
                ]);
        }

        $this->command->info('PerencanaanData seeder for Jawa Tengah completed successfully!');
        $this->command->info('Created ' . count($informasiUmumIds) + count($additionalInfoIds) . ' InformasiUmum records');
        $this->command->info('Created ' . count($informasiUmumIds) + count($additionalInfoIds) . ' PerencanaanData records');
    }
}
