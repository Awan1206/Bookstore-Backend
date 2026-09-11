<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * WAJIB dijalankan SETELAH CategorySeeder, karena butuh category_id.
     */
    public function run(): void
    {
        $novel = Category::where('name', 'Novel')->first();
        $mipa = Category::where('name', 'MIPA')->first();

        Book::firstOrCreate(['title' => 'Laskar Pelangi'], [
            'category_id' => $novel->id,
            'publish_date' => '2005-01-01',
            'publish_year' => 2005,
            'stock' => 20,
            'cost_price' => 40000,
            'sell_price' => 65000,
            'description' => 'Novel karya Andrea Hirata tentang perjuangan anak-anak Belitung.',
        ]);

        Book::firstOrCreate(['title' => 'Kalkulus Dasar'], [
            'category_id' => $mipa->id,
            'publish_date' => '2018-06-01',
            'publish_year' => 2018,
            'stock' => 15,
            'cost_price' => 55000,
            'sell_price' => 85000,
            'description' => 'Buku ajar kalkulus untuk mahasiswa tingkat awal.',
        ]);
    }
}