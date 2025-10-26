<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductsTableSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();

        DB::table('products')->insert([
            [
                'name' => 'Pulse Oximeter Fox 2',
                'sku' => 'FOX2',
                'description' => 'Alat pengukur kadar oksigen dalam darah dengan layar OLED dual warna.',
                'price' => 1360000,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Pulse Oximeter Fox 3',
                'sku' => 'FOX3',
                'description' => 'Oximeter dengan layar OLED warna dan kemampuan transmisi data ke komputer.',
                'price' => 3799000,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Sterilisator ZTP-80A',
                'sku' => 'ZTP80A',
                'description' => 'Sterilisator kering dengan kapasitas 80 liter, menggunakan ozon dan sinar inframerah.',
                'price' => 15000000,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Sterilisator ZTP-80AS',
                'sku' => 'ZTP80AS',
                'description' => 'Model upgrade dari ZTP-80A dengan desain dan fitur yang ditingkatkan.',
                'price' => 18000000,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Sterilisator GET-80C',
                'sku' => 'GET80C',
                'description' => 'Sterilisator kering dengan UPS, cocok untuk rumah sakit dan klinik.',
                'price' => 41964000,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'ECG-1200G',
                'sku' => 'ECG1200G',
                'description' => 'Electrocardiograph dengan baterai lithium yang dapat diisi ulang otomatis.',
                'price' => 25000000,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'ECG-300',
                'sku' => 'ECG300',
                'description' => 'Alat rekam EKG portabel dengan kemampuan cetak langsung.',
                'price' => 20000000,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
