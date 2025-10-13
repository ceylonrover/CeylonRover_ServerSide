<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create multiple users first
        User::factory(20)->create();

        // Create a specific test user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Create admin user
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@ceylonrover.lk',
            'role' => 'admin',
        ]);

        // Run blog seeder
        $this->call([
            BlogSeeder::class,
            SampleBlogSeeder::class,
            TravsnapSeeder::class,
        ]);
    }
}
