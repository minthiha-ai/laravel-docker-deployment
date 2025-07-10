<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\District;
use App\Models\Township;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TownshipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvFile = fopen(database_path('seeders/townships.csv'), 'r');
        $firstline = true;

        while (($data = fgetcsv($csvFile, 1000, ',')) !== false) {
            if (!$firstline) {
                if (District::where('D_Code', $data[2])->exists()) {
                    Township::create([
                        'id' => $data[0],
                        'SR_Code' => $data[1],
                        'D_Code' => $data[2],
                        'TS_Code' => $data[3],
                        'TS_Name' => $data[4],
                        'TS_Name_MMR' => $data[5],
                        'modifiled_by' => !empty($data[6]) && strtolower($data[6]) !== 'null' ? $data[6] : null,
                        'modifiled_on' => !empty($data[7]) && strtolower($data[7]) !== 'null' ? Carbon::parse($data[7]) : null,
                        'active' => $data[8] == 1,
                        'created_at' => !empty($data[9]) && strtolower($data[9]) !== 'null' ? Carbon::parse($data[9]) : now(),
                        'updated_at' => !empty($data[10]) && strtolower($data[10]) !== 'null' ? Carbon::parse($data[10]) : now(),
                    ]);
                }
            }
            $firstline = false;
        }

        fclose($csvFile);
    }
}
