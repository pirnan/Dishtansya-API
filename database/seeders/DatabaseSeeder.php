<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ProductSeeder::class,
        ]);

        // Create a default registered user for testing / evaluation convenience
        User::firstOrCreate(
            ['email' => 'backend@multisyscorp.com'],
            [
                'name' => 'Backend Developer',
                'password' => bcrypt('test123'),
            ]
        );
    }
}
