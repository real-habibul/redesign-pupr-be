<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use App\Models\Settings;

class RegionInitCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'region:init {region} {--module=perencanaan-data} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize region-specific data and settings';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $region = $this->argument('region');
        $module = $this->option('module');
        $force = $this->option('force');

        $this->info("Initializing region: {$region} for module: {$module}");

        // Validate region
        $supportedRegions = [
            'jateng' => [
                'name' => 'E-Katalog SIPASTI Jawa Tengah',
                'type' => 'provinsi',
                'logo' => '/storage/branding/jateng.png'
            ],
            'jabar' => [
                'name' => 'E-Katalog SIPASTI Jawa Barat',
                'type' => 'provinsi',
                'logo' => '/storage/branding/jabar.png'
            ],
            'jakarta' => [
                'name' => 'E-Katalog SIPASTI DKI Jakarta',
                'type' => 'provinsi',
                'logo' => '/storage/branding/jakarta.png'
            ]
        ];

        if (!array_key_exists($region, $supportedRegions)) {
            $this->error("Unsupported region: {$region}");
            $this->info("Supported regions: " . implode(', ', array_keys($supportedRegions)));
            return Command::FAILURE;
        }

        // Check if already initialized
        $existingRegion = Settings::get('org_region_code');
        if ($existingRegion === $region && !$force) {
            $this->warn("Region {$region} is already initialized. Use --force to reinitialize.");
            return Command::SUCCESS;
        }

        try {
            // Set organization settings
            $regionConfig = $supportedRegions[$region];
            Settings::set('org_name', $regionConfig['name'], 'string', 'Organization name');
            Settings::set('org_type', $regionConfig['type'], 'string', 'Organization type');
            Settings::set('org_region_code', $region, 'string', 'Organization region code');
            Settings::set('org_logo_url', $regionConfig['logo'], 'string', 'Organization logo URL');

            $this->info("✓ Organization settings configured for {$region}");

            // Run migrations if needed
            if ($force) {
                $this->info("Running migrations...");
                Artisan::call('migrate:fresh', ['--force' => true]);
                $this->info("✓ Migrations completed");
            }

            // Run module-specific seeder
            if ($module === 'perencanaan-data') {
                $seederClass = 'PerencanaanData' . ucfirst($region) . 'Seeder';

                if (class_exists("Database\\Seeders\\{$seederClass}")) {
                    $this->info("Running {$seederClass}...");
                    Artisan::call('db:seed', ['--class' => $seederClass, '--force' => true]);
                    $this->info("✓ Seeder completed");
                } else {
                    $this->warn("Seeder {$seederClass} not found. Creating sample data using default seeder.");
                    // Run a general seeder for the region
                    Artisan::call('db:seed', ['--class' => 'PerencanaanDataJatengSeeder', '--force' => true]);
                }
            }

            $this->info("🎉 Region initialization completed successfully!");
            $this->info("Region: {$region}");
            $this->info("Organization: {$regionConfig['name']}");
            $this->info("Type: {$regionConfig['type']}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to initialize region: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
