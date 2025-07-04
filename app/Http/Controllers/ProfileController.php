<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * Get the authenticated user's profile
     *
     * @return \Illuminate\Http\Response
     */
    public function getProfile()
    {
        \Log::info('Starting getProfile() method 1');

        $user = Auth::user();
        $userDetail = $user->detail;

        if (!$userDetail) {
            return response()->json([
                'message' => 'Profile not found'
            ], 404);
        }        // Prepare the response data
        $profile = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'profile_image' => $user->profile_image,
            'first_name' => $userDetail->first_name,
            'last_name' => $userDetail->last_name,
            'location' => $userDetail->location,
            'joined_date' => $userDetail->joined_date,
            'bio' => $userDetail->bio,
            'mobile_number' => $userDetail->mobile_number,
            'profile_image_path' => $userDetail->profile_image_path,
            'social_links' => [
                'x' => $userDetail->x_link,
                'instagram' => $userDetail->instagram_link,
                'facebook' => $userDetail->facebook_link,
                'linkedin' => $userDetail->linkedin_link,
            ],
            'stats' => [
                'blog_count' => $userDetail->blog_count,
                'travsnap_count' => $userDetail->travsnap_count,
                'total_likes' => $userDetail->total_likes,
                'total_views' => $userDetail->total_views,
            ]
        ];

        return response()->json([
            'success' => true,
            'profile' => $profile
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Update the user's profile
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
          // Validate the request data
        $validator = Validator::make($request->all(), [
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'x_link' => 'nullable|url|max:255',
            'instagram_link' => 'nullable|url|max:255',
            'facebook_link' => 'nullable|url|max:255',
            'linkedin_link' => 'nullable|url|max:255',
            'mobile_number' => 'nullable|string|max:20',
            'profile_image_path' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Get or create the user detail record
        $userDetail = $user->detail;
        
        if (!$userDetail) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found. Please verify your email first.'
            ], 404);
        }        // Update the user detail
        $userDetail->update([
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
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully'
        ]);
    }

    /**
     * Upload a profile image
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */    public function uploadProfileImage(Request $request)
    {
        $user = Auth::user();
        $userDetail = $user->detail;

        if (!$userDetail) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found. Please verify your email first.'
            ], 404);
        }

        // Check if request is multipart form data with file upload
        if ($request->hasFile('profile_image')) {
            $validator = Validator::make($request->all(), [
                'profile_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $image = $request->file('profile_image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/profiles'), $imageName);

            // Update user detail with the new image path
            $imagePath = 'images/profiles/' . $imageName;
            $userDetail->profile_image_path = $imagePath;
            $userDetail->save();

            return response()->json([
                'success' => true,
                'message' => 'Profile image uploaded successfully',
                'image_path' => $imagePath
            ], 200, [], JSON_UNESCAPED_SLASHES);
        }
        // Check if request contains binary data and content-type header
        else if ($request->getContent() && $request->header('Content-Type')) {
            $contentType = $request->header('Content-Type');
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            
            if (!in_array($contentType, $allowedTypes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid image format. Allowed formats: JPEG, PNG, JPG, GIF'
                ], 422);
            }

            // Get file extension from content type
            $extension = str_replace('image/', '', $contentType);
            if ($extension === 'jpeg') $extension = 'jpg';
            
            // Generate filename
            $imageName = time() . '.' . $extension;
            
            // Save binary data to file
            $binaryData = $request->getContent();
            
            // Check file size (max 2MB)
            if (strlen($binaryData) > 2048 * 1024) {
                return response()->json([
                    'success' => false,
                    'message' => 'Image size exceeds the maximum limit of 2MB'
                ], 422);
            }
            
            // Ensure the directory exists
            if (!file_exists(public_path('images/profiles'))) {
                mkdir(public_path('images/profiles'), 0777, true);
            }
            
            // Save the file
            file_put_contents(public_path('images/profiles/' . $imageName), $binaryData);
            
            // Update user detail with the new image path
            $imagePath = 'images/profiles/' . $imageName;
            $userDetail->profile_image_path = $imagePath;
            $userDetail->save();

            return response()->json([
                'success' => true,
                'message' => 'Profile image uploaded successfully',
                'image_path' => $imagePath
            ], 200, [], JSON_UNESCAPED_SLASHES);
        }

        return response()->json([
            'success' => false,
            'message' => 'No image data provided'
        ], 400);
    }
}
