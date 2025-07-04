<?php

// This is a test script to verify partial profile updates
// To run: php test_partial_update.php

// Simulate a request with only one field
$request = new stdClass();
$request->bio = "This is a test bio update";

// Simulate the current user detail record
$userDetail = new stdClass();
$userDetail->first_name = "John";
$userDetail->last_name = "Doe";
$userDetail->location = "Colombo";
$userDetail->bio = "Original bio";
$userDetail->x_link = "https://x.com/johndoe";
$userDetail->instagram_link = "https://instagram.com/johndoe";
$userDetail->facebook_link = "https://facebook.com/johndoe";
$userDetail->linkedin_link = "https://linkedin.com/in/johndoe";
$userDetail->mobile_number = "+94123456789";
$userDetail->profile_image_path = "/images/profiles/johndoe.jpg";

// Simulate update operation
$updated = [
    'first_name' => $request->first_name ?? $userDetail->first_name,
    'last_name' => $request->last_name ?? $userDetail->last_name,
    'location' => $request->location ?? $userDetail->location,
    'bio' => $request->bio ?? $userDetail->bio,
    'x_link' => $request->x_link ?? $userDetail->x_link,
    'instagram_link' => $request->instagram_link ?? $userDetail->instagram_link,
    'facebook_link' => $request->facebook_link ?? $userDetail->facebook_link,
    'linkedin_link' => $request->linkedin_link ?? $userDetail->linkedin_link,
    'mobile_number' => $request->mobile_number ?? $userDetail->mobile_number,
    'profile_image_path' => $request->profile_image_path ?? $userDetail->profile_image_path,
];

// Print the result
echo "Original Data:\n";
print_r($userDetail);

echo "\nRequest Data (partial update):\n";
print_r($request);

echo "\nResult After Update:\n";
print_r($updated);

// Verify the update worked correctly
echo "\nVerification:\n";
echo "- Bio was updated: " . ($updated['bio'] === $request->bio ? "YES" : "NO") . "\n";
echo "- Other fields remained unchanged: " . 
    ($updated['first_name'] === $userDetail->first_name &&
     $updated['last_name'] === $userDetail->last_name &&
     $updated['location'] === $userDetail->location &&
     $updated['x_link'] === $userDetail->x_link &&
     $updated['instagram_link'] === $userDetail->instagram_link &&
     $updated['facebook_link'] === $userDetail->facebook_link &&
     $updated['linkedin_link'] === $userDetail->linkedin_link &&
     $updated['mobile_number'] === $userDetail->mobile_number &&
     $updated['profile_image_path'] === $userDetail->profile_image_path
     ? "YES" : "NO") . "\n";
