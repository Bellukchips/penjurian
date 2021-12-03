<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'name' => 'Admin Penjurian',
            'email' => 'adminpenjurian@gmail.com',
            'password' => Hash::make('adminpenjurian'),
            'roles' => 'ADMIN',
        ]);
    }
}
