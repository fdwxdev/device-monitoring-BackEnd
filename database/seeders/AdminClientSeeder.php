 <?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminClientSeeder extends Seeder
{
    public function run(): void
{
    User::factory()->create([
        'name' => 'Admin Client',
        'email' => 'adminClient@example.com',
        'password' => Hash::make('admin1234'),
        'role' => 'adminclient',
    ]);
}
} 