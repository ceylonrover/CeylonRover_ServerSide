<?php

namespace Database\Seeders;

use App\Models\Travsnap;
use App\Models\TravsnapModeration;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Faker\Factory as Faker;

class TravsnapSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */    public function run(): void
    {
        $faker = Faker::create();
          // Use specific user ID 17
        $userId = 17;
        $user = User::with('detail')->find($userId);
        
        // If user ID 17 doesn't exist, create it
        if (!$user) {
            $user = User::factory()->create([
                'id' => $userId,
                'role' => 'user',
                'email_verified_at' => now(),
            ]);
            
            // Create user detail record
            \App\Models\UserDetail::create([
                'user_id' => $userId,
                'first_name' => 'Test',
                'last_name' => 'User',
                'profile_image_path' => 'https://source.unsplash.com/random/200x200/?profile',
            ]);
            
            // Reload user with detail
            $user = User::with('detail')->find($userId);
            
            $this->command->info('User ID 17 created as it did not exist');
        } else {
            // Display user details
            $detail = $user->detail;
            $firstName = $detail ? $detail->first_name : 'N/A';
            $lastName = $detail ? $detail->last_name : 'N/A';
            $profileImage = $detail ? $detail->profile_image_path : 'N/A';
            
            $this->command->info("Using existing user ID 17:");
            $this->command->info("First Name: $firstName");
            $this->command->info("Last Name: $lastName");
            $this->command->info("Profile Image: $profileImage");
        }
        
        // Find a super admin for moderation
        $superAdmin = User::where('role', 'superAdmin')->first();
        if (!$superAdmin) {
            $superAdmin = User::factory()->create([
                'role' => 'superAdmin',
                'email_verified_at' => now(),
            ]);
        }
        
        // Sri Lanka locations
        $districts = [
            'Colombo', 'Gampaha', 'Kalutara', 'Kandy', 'Matale', 'Nuwara Eliya', 
            'Galle', 'Matara', 'Hambantota', 'Jaffna', 'Kilinochchi', 'Mannar', 
            'Mullaitivu', 'Vavuniya', 'Trincomalee', 'Batticaloa', 'Ampara', 
            'Badulla', 'Monaragala', 'Ratnapura', 'Kegalle', 'Anuradhapura', 
            'Polonnaruwa', 'Puttalam', 'Kurunegala'
        ];
        
        $provinces = [
            'Western', 'Central', 'Southern', 'Northern', 'Eastern', 
            'North Western', 'North Central', 'Uva', 'Sabaragamuwa'
        ];
        
        $popularPlaces = [
            'Sigiriya', 'Ella', 'Mirissa', 'Unawatuna', 'Hikkaduwa', 'Haputale', 
            'Arugam Bay', 'Weligama', 'Tangalle', 'Dambulla', 'Anuradhapura', 
            'Polonnaruwa', 'Kandy', 'Galle Fort', 'Yala National Park', 
            'Udawalawe National Park', 'Adams Peak', 'Horton Plains', 
            'Nine Arch Bridge', 'Pidurangala', 'Mihintale', 'Trincomalee', 
            'Jaffna', 'Nuwara Eliya', 'Colombo', 'Negombo', 'Bentota'
        ];
        
        $placeTypes = [
            'Beach', 'Mountain', 'Waterfall', 'National Park', 'Temple', 
            'Historical Site', 'Lake', 'River', 'Cave', 'Town', 'City', 
            'Viewpoint', 'Forest', 'Island', 'Museum', 'Garden'
        ];
        
        // Generate random places in Sri Lanka
        $places = [];
        for ($i = 0; $i < 50; $i++) {
            $district = $faker->randomElement($districts);
            $province = $faker->randomElement($provinces);
            
            // Use popular place name or generate a random one
            if ($i < count($popularPlaces)) {
                $placeName = $popularPlaces[$i];
            } else {
                $placeType = $faker->randomElement($placeTypes);
                $placeName = $faker->randomElement([
                    $faker->firstName . ' ' . $placeType,
                    $faker->lastName . ' ' . $placeType,
                    $placeType . ' of ' . $faker->firstName,
                    $faker->word . ' ' . $placeType
                ]);
            }
            
            $places[] = [
                'name' => $placeName,
                'district' => $district,
                'province' => $province,
                'lat' => $faker->latitude(5.9, 9.9), // Sri Lanka latitude range
                'lng' => $faker->longitude(79.5, 81.9), // Sri Lanka longitude range
            ];
        }
          // Create 50 travsnaps
        for ($i = 0; $i < 50; $i++) {
            // Select a random place
            $place = $places[$i % count($places)];
            
            // Generate random image URLs
            $galleryCount = rand(3, 6);
            $gallery = [];
            for ($j = 0; $j < $galleryCount; $j++) {
                $imageKeywords = urlencode($place['name'] . ' sri lanka ' . $faker->randomElement($placeTypes));
                $gallery[] = 'https://source.unsplash.com/random/800x600?' . $imageKeywords . '&sig=' . ($i * 10 + $j);
            }
            
            // Create the travsnap
            try {
                $travsnap = Travsnap::create([
                    'user_id' => $userId, // Always use user ID 17
                    'title' => $place['name'] . ' - ' . $faker->sentence(rand(3, 6)),
                    'description' => $faker->paragraphs(rand(2, 4), true),
                    'location' => [
                        'name' => $place['name'],
                        'district' => $place['district'],
                        'province' => $place['province'],
                        'lat' => $place['lat'],
                        'lng' => $place['lng'],
                        'address' => $faker->address,
                    ],
                    'gallery' => $gallery,
                    'status' => 'approved',
                    'is_featured' => $faker->boolean(20), // 20% chance of being featured
                    'is_active' => true,
                    'created_at' => $faker->dateTimeBetween('-6 months', 'now'),
                    'updated_at' => $faker->dateTimeBetween('-3 months', 'now'),
                ]);
                
                // Create a moderation record for each travsnap
                $moderation = TravsnapModeration::create([
                    'travsnap_id' => $travsnap->id,
                    'moderator_id' => $superAdmin->id,
                    'status' => 'approved',
                    'moderator_notes' => 'Approved by system',
                    'published_at' => now(),
                    'is_active' => true,
                ]);
                
                // Update travsnap with moderation_id
                $travsnap->update(['moderation_id' => $moderation->id]);
                
            } catch (\Exception $e) {
                Log::error('Error creating travsnap: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);            }
        }
        
        $this->command->info('50 sample travsnaps created successfully for user ID 17');
    }
}
