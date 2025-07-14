<?php
// Reset and seed travsnap data
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

try {
    echo "Checking user ID 17 details...\n";
    
    // Check user ID 17 details
    $user = DB::table('users')->where('id', 17)->first();
    if ($user) {
        $userDetail = DB::table('user_details')->where('user_id', 17)->first();
        if ($userDetail) {
            echo "User ID 17 found with details:\n";
            echo "Name: {$user->name}\n";
            echo "Email: {$user->email}\n";
            echo "First Name: " . ($userDetail->first_name ?? 'N/A') . "\n";
            echo "Last Name: " . ($userDetail->last_name ?? 'N/A') . "\n";
            echo "Profile Image: " . ($userDetail->profile_image_path ?? 'N/A') . "\n";
        } else {
            echo "User ID 17 exists but has no details record.\n";
        }
    } else {
        echo "User ID 17 does not exist, will be created by the seeder.\n";
    }
    
    echo "Clearing existing travsnap data...\n";
    
    // Disable foreign key checks
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    
    // Truncate travsnap tables
    DB::table('travsnap_moderations')->truncate();
    DB::table('travsnaps')->truncate();
    
    // Re-enable foreign key checks
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    
    echo "Existing travsnap data cleared.\n";
    echo "Running travsnap seeder...\n";
    
    // Run the seeder
    $kernel->call('db:seed', [
        '--class' => 'Database\Seeders\TravsnapSeeder',
        '--force' => true
    ]);
    
    echo "Done! 50 travsnaps created for user ID 17.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    Log::error('Error in reset_and_seed_travsnaps.php: ' . $e->getMessage(), [
        'trace' => $e->getTraceAsString()
    ]);
}
