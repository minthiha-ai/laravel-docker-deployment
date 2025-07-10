<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\District;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DistrictSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvFile = fopen(database_path('seeders/districts.csv'), 'r');
        $firstline = true;

        while (($data = fgetcsv($csvFile, 1000, ',')) !== false) {
            if (!$firstline) {
                District::create([
                    'id' => $data[0],
                    'D_Code' => $data[1],
                    'D_Name' => $data[2],
                    'D_Name_MMR' => $data[3],
                    'SR_Code' => $data[4],
                    'modifiled_by' => !empty($data[5]) && strtolower($data[5]) !== 'null' ? $data[5] : null,
                    'modifiled_on' => !empty($data[6]) && strtolower($data[6]) !== 'null' ? Carbon::parse($data[6]) : null,
                    'active' => $data[7] == 1,
                    'created_at' => !empty($data[8]) && strtolower($data[8]) !== 'null' ? Carbon::parse($data[8]) : now(),
                    'updated_at' => !empty($data[9]) && strtolower($data[9]) !== 'null' ? Carbon::parse($data[9]) : now(),
                ]);
            }
            $firstline = false;
        }

        fclose($csvFile);
    }
}
