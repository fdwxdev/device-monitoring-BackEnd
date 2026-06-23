<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

       /*  User::create([
            'name' => 'Super Admin',
            'email' => 'admin@1234.com',
            'password' => Hash::make('1234'),
            'role' => 'superadmin',
        ]);

        User::create([
            'name' => 'Admin Client',
            'email' => 'adminclient@example.com',
            'password' => Hash::make('admin1234'),
            'role' => 'adminclient',
        ]);

        User::create([
            'name' => 'Supervisor',
            'email' => 'supervisor@example.com',
            'password' => Hash::make('12345678'),
            'role' => 'supervisor',
        ]); */
  
}
    }


   