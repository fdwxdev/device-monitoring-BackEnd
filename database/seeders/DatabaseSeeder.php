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

    //     User::factory()->create([
    //         'name' => 'Super Admin',
    //         'email' => 'superAdmin@example.com',
    //         'password' => 'admin1234',
    //         'role' => 'superadmin',

    //     ]);
    //      $this->call([
    //     AdminClientSeeder::class,  // ]);

    DB::table('users')->insert([
            'name' => 'supervisor',
            'email' => 'supervisor@example.com',
            'password' => Hash::make('12345678'),
            'role' => 'supervisor',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
  
}
    }


   