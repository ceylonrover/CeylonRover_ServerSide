<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\BlogModeration;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class SampleBlogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $userId = 17; // Using user ID 17 as specified
        
        // Towns, villages, districts, and provinces in Sri Lanka
        $towns = ['Colombo', 'Kandy', 'Galle', 'Negombo', 'Trincomalee', 'Batticaloa', 'Jaffna', 'Matara', 'Anuradhapura', 'Nuwara Eliya'];
        $villages = ['Sigiriya', 'Ella', 'Mirissa', 'Unawatuna', 'Hikkaduwa', 'Haputale', 'Arugam Bay', 'Weligama', 'Tangalle', 'Dambulla'];
        $districts = ['Colombo', 'Gampaha', 'Kalutara', 'Kandy', 'Matale', 'Nuwara Eliya', 'Galle', 'Matara', 'Hambantota', 'Jaffna', 'Kilinochchi', 'Mannar', 'Mullaitivu', 'Vavuniya', 'Trincomalee', 'Batticaloa', 'Ampara', 'Badulla', 'Monaragala', 'Ratnapura', 'Kegalle', 'Anuradhapura', 'Polonnaruwa', 'Puttalam', 'Kurunegala'];
        $provinces = ['Western', 'Central', 'Southern', 'Northern', 'Eastern', 'North Western', 'North Central', 'Uva', 'Sabaragamuwa'];
        $regions = ['Coastal', 'Hill Country', 'Dry Zone', 'Wet Zone', 'Cultural Triangle', 'Tea Country'];
        
        // Blog types
        $types = ['News', 'Articles', 'Experiences'];
        
        // Categories
        $categories = [
            'Waterfall', 'Hill', 'Lake', 'Forest', 'Beach', 'Island', 'River',
            'Adventure', 'Safari', 'Cruise', 'Camping', 'Cultural & religious',
            'Park', 'Event', 'View point', 'Town/City'
        ];
        
        // Create 50 blogs
        for ($i = 0; $i < 50; $i++) {
            // Generate random title and slug
            $title = $faker->sentence(rand(4, 8));
            $slug = Str::slug($title) . '-' . uniqid();
            
            // Generate random location data
            $location = [
                'town' => $faker->randomElement($towns),
                'village' => $faker->randomElement($villages),
                'district' => $faker->randomElement($districts),
                'province' => $faker->randomElement($provinces),
                'address' => $faker->address,
                'lat' => $faker->latitude(5.9, 9.9), // Sri Lanka latitude range
                'lng' => $faker->longitude(79.5, 81.9), // Sri Lanka longitude range
                'region' => $faker->randomElement($regions)
            ];
            
            // Generate random categories (2-4 categories per blog)
            $blogCategories = $faker->randomElements($categories, rand(2, 4));
            
            // Create the blog
            $blog = Blog::create([
                'title' => $title,
                'slug' => $slug,
                'description' => $faker->paragraph(rand(2, 5)),
                'additionalInfo' => $faker->paragraph(rand(1, 3)),
                'content' => $faker->paragraphs(rand(5, 10), true),
                'user_id' => $userId,
                'categories' => json_encode($blogCategories),
                'location' => json_encode($location),
                'image' => 'https://source.unsplash.com/random/800x600?sri+lanka,' . $blogCategories[0],
                'gallery' => json_encode([
                    'https://source.unsplash.com/random/800x600?sri+lanka,' . $blogCategories[0],
                    'https://source.unsplash.com/random/800x600?sri+lanka,' . $blogCategories[1]
                ]),
                'review' => $faker->paragraph(),
                'operatingHours' => '8:00 AM - 6:00 PM',
                'entryFee' => 'LKR ' . $faker->numberBetween(100, 5000),
                'suitableFor' => json_encode(['Families', 'Couples', 'Solo Travelers', 'Groups']),
                'specialty' => $faker->sentence(),
                'closedDates' => 'Closed on ' . $faker->dayOfWeek,
                'routeDetails' => $faker->paragraph(),
                'safetyMeasures' => $faker->paragraph(),
                'restrictions' => $faker->sentence(),
                'climate' => $faker->randomElement(['Tropical', 'Moderate', 'Dry', 'Humid', 'Cool']),
                'travelAdvice' => $faker->paragraph(),
                'emergencyContacts' => 'Emergency: 119, Tourist Police: ' . $faker->phoneNumber,
                'assistance' => $faker->sentence(),
                'type' => $faker->randomElement($types),
                'views' => $faker->numberBetween(10, 5000),
                'status' => 'approved',
                'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => $faker->dateTimeBetween('-6 months', 'now'),
            ]);
            
            // Create a moderation record for each blog
            BlogModeration::create([
                'blog_id' => $blog->id,
                'moderator_id' => $userId, // Using the same user as moderator
                'status' => 'approved',
                'moderator_notes' => 'Approved by system',
                'published_at' => now(),
                'is_active' => true,
            ]);
        }
        
        $this->command->info('50 sample blogs created successfully with user ID 17');
    }
}
