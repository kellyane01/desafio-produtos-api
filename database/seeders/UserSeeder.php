<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'brena@gmail.com'],
            [
                'name' => 'Brena',
                'password' => Hash::make('12345678'),
            ]
        );
    }
}
