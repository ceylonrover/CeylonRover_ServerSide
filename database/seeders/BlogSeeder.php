<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Blog;
use App\Models\User;
use App\Models\BlogModeration;
use Illuminate\Support\Str;
use Faker\Factory as Faker;
use Carbon\Carbon;

class BlogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */    public function run()
    {
        $faker = Faker::create();
        
        // Get existing users for the blogs
        $users = User::all();
        if ($users->count() == 0) {
            // Create a default user only if there are none
            User::create([
                'name' => 'Default User',
                'email' => 'default@example.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
            $users = User::all();
        }
        
        // Define categories to use
        $categories = [
            'Beach', 'Mountain', 'Adventure', 'Luxury', 'Budget', 'Family-friendly',
            'Solo Travel', 'Cultural', 'Historical', 'Wildlife', 'Food', 'Nature',
            'Hiking', 'Trekking', 'Camping', 'Surfing', 'Diving', 'Safari',
            'Waterfall', 'Temple', 'Ancient Ruins', 'Village', 'City', 'Hotel Review'
        ];
        
        // Create 50 blogs with various properties to test filtering
        for ($i = 0; $i < 50; $i++) {
            // Pick a random number of categories (1-4)
            $blogCategories = $faker->randomElements(
                $categories, 
                $faker->numberBetween(1, 4)
            );
            
            // Random location in Sri Lanka
            $location = [
                'lat' => $faker->latitude(5.9, 9.8),
                'lng' => $faker->longitude(79.7, 81.9),
                'name' => $faker->city . ', Sri Lanka'
            ];
            
            // Generate published date - some recent, some older
            $createdDate = $faker->dateTimeBetween('-2 years', 'now');
            $publishedDate = (clone $createdDate)->modify('+' . $faker->numberBetween(1, 7) . ' days');
            
            // Set creation date
            $createdAt = Carbon::instance($createdDate);
            
            // Randomize views, likes, and status
            $views = $faker->numberBetween(10, 5000);
            
            // Generate a unique title
            $title = $faker->randomElement([
                'Exploring', 'Discovering', 'Guide to', 'Best of', 'Hidden Gems in', 
                'Top Secrets of', 'Adventure in', 'Journey through', 'Ultimate', 
                'Magical', 'Breathtaking', 'Enchanting', 'Spectacular', 'Unforgettable'
            ]) . ' ' . $faker->randomElement([
                'Colombo', 'Kandy', 'Galle', 'Sigiriya', 'Ella', 'Nuwara Eliya',
                'Mirissa', 'Unawatuna', 'Dambulla', 'Trincomalee', 'Jaffna', 'Arugam Bay',
                'Hikkaduwa', 'Yala', 'Wilpattu', 'Anuradhapura', 'Polonnaruwa', 'Matara',
                'Batticaloa', 'Tangalle', 'Horton Plains', 'Adam\'s Peak', 'Bentota', 'Negombo'
            ]);
            
            // Create the blog
            $blog = Blog::create([
                'title' => $title,
                'slug' => Str::slug($title) . '-' . $faker->numberBetween(1, 999),
                'description' => $faker->paragraph(2),
                'content' => $faker->paragraphs(10, true),
                'user_id' => $users->random()->id,
                'categories' => json_encode($blogCategories),
                'location' => json_encode($location),
                'image' => 'https://picsum.photos/id/' . $faker->numberBetween(1, 1000) . '/800/600',
                'gallery' => json_encode([
                    'https://picsum.photos/id/' . $faker->numberBetween(1, 1000) . '/800/600',
                    'https://picsum.photos/id/' . $faker->numberBetween(1, 1000) . '/800/600',
                    'https://picsum.photos/id/' . $faker->numberBetween(1, 1000) . '/800/600'
                ]),
                'operatingHours' => $faker->randomElement(['24/7', '9AM-5PM', '8AM-6PM', 'Sunrise to Sunset']),
                'entryFee' => $faker->randomElement(['Free', '$5', '$10-$20', 'Rs. 500', 'Rs. 1000-2000']),
                'suitableFor' => json_encode($faker->randomElements(['Couples', 'Families', 'Solo Travelers', 'Groups', 'Children', 'Elderly'], $faker->numberBetween(1, 3))),
                'specialty' => $faker->sentence,
                'type' => $faker->randomElement(['Destination', 'Activity', 'Accommodation', 'Restaurant', 'Travel Guide']),
                'views' => $views,
                'status' => $faker->randomElement(['pending', 'approved', 'rejected']),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
            
            // Add moderation record
            $status = $blog->status;
            BlogModeration::create([
                'blog_id' => $blog->id,
                'moderator_id' => $users->random()->id,
                'status' => $status,
                'moderator_notes' => $status === 'rejected' ? $faker->sentence : null,
                'is_active' => true,
                'published_at' => $status === 'approved' ? Carbon::instance($publishedDate) : null,
                'rejected_at' => $status === 'rejected' ? Carbon::instance($publishedDate) : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
            
            // Randomly add bookmarks (likes) to some blogs
            $likesCount = $faker->numberBetween(0, 100);
            $likerIds = $users->random(min($likesCount, $users->count()))->pluck('id')->toArray();
            
            if (count($likerIds) > 0) {
                $blog->bookmarkedBy()->attach($likerIds);
            }
        }
    }
}
