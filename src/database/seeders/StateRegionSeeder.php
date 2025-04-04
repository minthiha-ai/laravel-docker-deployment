<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\StateRegion;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class StateRegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvFile = fopen(database_path('seeders/state_regions.csv'), 'r');
        $firstline = true;

        while (($data = fgetcsv($csvFile, 1000, ',')) !== false) {
            if (!$firstline) {
                StateRegion::create([
                    'id' => $data[0],
                    'SR_Code' => $data[1],
                    'SR_Name' => $data[2],
                    'SR_Name_MMR' => $data[3],
                    'modifiled_by' => !empty($data[4]) && strtolower($data[4]) !== 'null' ? $data[4] : null,
                    'modifiled_on' => !empty($data[5]) && strtolower($data[5]) !== 'null' ? Carbon::parse($data[5]) : null,
                    'active' => $data[6] == 1,
                    'created_at' => !empty($data[7]) && strtolower($data[7]) !== 'null' ? Carbon::parse($data[7]) : now(),
                    'updated_at' => !empty($data[8]) && strtolower($data[8]) !== 'null' ? Carbon::parse($data[8]) : now(),
                ]);
            }
            $firstline = false;
        }

        fclose($csvFile);
    }
}
