<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Travsnap;
use App\Models\TravsnapModeration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class TravsnapController extends Controller
{
    /**
     * Display a listing of the resource.
     * Get all approved travsnaps
     */
    public function getAllTravsnaps()
    {
        Log::info('TravsnapController@getAllTravsnaps method called');
        
        try {            $travsnaps = Travsnap::where('status', 'approved')
                ->where('is_active', true)
                ->with('user.detail')
                ->get();

            // Transform travsnaps to include user details
            $transformedTravsnaps = $travsnaps->map(function($travsnap) {
                $data = $travsnap->toArray();
                
                // Add user details in the proper format
                if (isset($data['user']) && isset($data['user']['detail'])) {
                    $userDetail = $data['user']['detail'];
                    $data['user'] = [
                        'id' => $data['user']['id'],
                        'name' => $data['user']['name'] ?? null,
                        'email' => $data['user']['email'] ?? null,
                        'first_name' => $userDetail['first_name'] ?? null,
                        'last_name' => $userDetail['last_name'] ?? null,
                        'profile_image_url' => $userDetail['profile_image_path'] ?? null
                    ];
                }
                
                return $data;
            });

            return response()->json([
                'message' => 'Travsnaps retrieved successfully', 
                'travsnaps' => $transformedTravsnaps
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in TravsnapController@getAllTravsnaps', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get a specific travsnap by ID
     */
    public function getById($id)
    {
        Log::info('TravsnapController@getById method called', ['id' => $id]);
        
        try {
            $travsnap = Travsnap::where('id', $id)
                ->where('is_active', true)
                ->with('user:id,name,email,profile_photo')
                ->first();
            
            if (!$travsnap) {
                return response()->json(['message' => 'Travsnap not found'], 404);
            }

            return response()->json([
                'message' => 'Travsnap retrieved successfully', 
                'travsnap' => $travsnap
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in TravsnapController@getById', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info('TravsnapController@store method called', ['ip' => $request->ip()]);

        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'location' => 'required|array',
                'gallery' => 'required|array|min:1',
                'user_id' => 'required|integer|exists:users,id',
            ]);

            // Create the travsnap first to get its ID
            $travsnap = Travsnap::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'location' => $validated['location'],
                'gallery' => [], // Will be updated after saving images
                'user_id' => $validated['user_id'],
                'status' => 'pending',
                'is_featured' => false,
                'is_active' => true,
            ]);

            // Now use the travsnap_id for the folder path
            $folderPath = 'travsnap/' . $travsnap->id;
            
            // Process gallery images
            $galleryPaths = [];
            if (!empty($validated['gallery']) && is_array($validated['gallery'])) {
                foreach ($validated['gallery'] as $index => $base64Image) {
                    if (is_string($base64Image) && str_starts_with($base64Image, 'data:image')) {
                        $galleryPaths[] = $this->saveBase64ImageToFolder($base64Image, $folderPath, 'img' . ($index + 1));
                    } else {
                        $galleryPaths[] = $base64Image;
                    }
                }
            }            // Update the travsnap with gallery paths
            $travsnap->update([
                'gallery' => $galleryPaths
            ]);
            
            // Find superadmin to assign as moderator (be specific with the role)
            $superAdmin = \App\Models\User::where('role', 'superAdmin')->first();
            
            // Log the superadmin details for debugging
            if ($superAdmin) {
                Log::info('SuperAdmin found for moderation assignment', [
                    'superadmin_id' => $superAdmin->id,
                    'superadmin_email' => $superAdmin->email,
                    'travsnap_id' => $travsnap->id
                ]);
            } else {
                Log::warning('No SuperAdmin found for moderation assignment', [
                    'travsnap_id' => $travsnap->id
                ]);
            }
            
            // Create initial moderation record
            $moderation = TravsnapModeration::create([
                'travsnap_id' => $travsnap->id,
                'moderator_id' => $superAdmin ? $superAdmin->id : $validated['user_id'], // Set superadmin as moderator if available, otherwise fallback to creator
                'status' => 'pending',
                'moderator_notes' => null,
                'is_active' => true,
            ]);
            
            // If there's a super admin in the system, also create a moderator assignment record
            if ($superAdmin) {
                \App\Models\ModeratorAssignment::create([
                    'moderator_id' => $superAdmin->id,
                    'content_id' => $travsnap->id,
                    'content_type' => 'travsnap',
                    'is_active' => true
                ]);
            }

            // Update travsnap with moderation_id
            $travsnap->update(['moderation_id' => $moderation->id]);

            return response()->json([
                'message' => 'Travsnap created successfully', 
                'travsnap' => $travsnap
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error in TravsnapController@store', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        Log::info('TravsnapController@update method called', ['id' => $id, 'ip' => $request->ip()]);
        
        try {
            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'location' => 'sometimes|array',
                'gallery' => 'sometimes|array',
                'status' => 'sometimes|in:pending,approved,rejected',
            ]);

            // Find the travsnap by ID
            $travsnap = Travsnap::findOrFail($id);
            
            // Use travsnap_id for folder path
            $folderPath = 'travsnap/' . $travsnap->id;

            // Process gallery images
            $galleryPaths = [];
            if (!empty($validated['gallery']) && is_array($validated['gallery'])) {
                // Get existing gallery images
                $existingGallery = is_array($travsnap->gallery) ? $travsnap->gallery : [];
                $galleryCount = count($existingGallery);
                
                foreach ($validated['gallery'] as $index => $image) {
                    if (is_string($image) && str_starts_with($image, 'data:image')) {
                        // Save new image with incremented index
                        $galleryPaths[] = $this->saveBase64ImageToFolder($image, $folderPath, 'img' . ($galleryCount + $index + 1));
                    } else {
                        // Keep existing image path
                        $galleryPaths[] = $image;
                    }
                }
                $validated['gallery'] = array_merge($existingGallery, $galleryPaths);
            }

            // Update the travsnap with the validated data
            $travsnap->update([
                'title' => $validated['title'] ?? $travsnap->title,
                'description' => $validated['description'] ?? $travsnap->description,
                'location' => $validated['location'] ?? $travsnap->location,
                'gallery' => $validated['gallery'] ?? $travsnap->gallery,
                'status' => $validated['status'] ?? $travsnap->status,
            ]);

            return response()->json([
                'message' => 'Travsnap updated successfully', 
                'travsnap' => $travsnap
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in TravsnapController@update', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy($id)
    {
        Log::info('TravsnapController@destroy method called', ['id' => $id]);
        
        try {
            $travsnap = Travsnap::findOrFail($id);
            $travsnap->update(['is_active' => false]);

            return response()->json(['message' => 'Travsnap deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Error in TravsnapController@destroy', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }    /**
     * Get all pending travsnaps (for admin).
     */    public function getPending()
    {
        Log::info('TravsnapController@getPending method called');
        
        try {
            // Check if user is admin or super admin
            if (!Auth::user() || !in_array(Auth::user()->role, ['admin', 'superAdmin'])) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // If super admin, get all pending travsnaps
            if (Auth::user()->role === 'superAdmin') {
                $travsnaps = Travsnap::where('status', 'pending')
                    ->where('is_active', true)
                    ->with('user:id,name,email,profile_photo')
                    ->get();
            } else {
                // For regular admins, get only travsnaps assigned to them
                $assignedTravsnapIds = \App\Models\ModeratorAssignment::where('moderator_id', Auth::id())
                    ->where('content_type', 'travsnap')
                    ->where('is_active', true)
                    ->pluck('content_id')
                    ->toArray();
                    
                $travsnaps = Travsnap::where('status', 'pending')
                    ->where('is_active', true)
                    ->whereIn('id', $assignedTravsnapIds)
                    ->with('user:id,name,email,profile_photo')
                    ->get();
            }

            return response()->json([
                'message' => 'Pending travsnaps retrieved successfully', 
                'travsnaps' => $travsnaps
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in TravsnapController@getPending', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Approve a travsnap (admin only).
     */    public function approve(Request $request, $id)
    {
        Log::info('TravsnapController@approve method called', ['id' => $id]);
        
        try {
            // Check if user is admin or super admin
            if (!Auth::user() || !in_array(Auth::user()->role, ['admin', 'superAdmin'])) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }            $travsnap = Travsnap::findOrFail($id);
            
            // Get the superadmin for moderation assignment
            $superAdmin = \App\Models\User::where('role', 'superAdmin')->first();
            $moderatorId = $superAdmin ? $superAdmin->id : Auth::id();
            
            // Update travsnap status
            $travsnap->update(['status' => 'approved']);
            
            // Create new moderation record
            $moderation = TravsnapModeration::create([
                'travsnap_id' => $travsnap->id,
                'moderator_id' => $moderatorId, // Use superadmin if available, otherwise current authenticated user
                'status' => 'approved',
                'moderator_notes' => $request->input('notes', 'Approved by admin'),
                'published_at' => now(),
                'is_active' => true,
            ]);
            
            // Update previous active moderation
            if ($travsnap->moderation_id) {
                TravsnapModeration::where('id', $travsnap->moderation_id)
                    ->update(['is_active' => false]);
            }
            
            // Deactivate moderator assignments for this travsnap
            \App\Models\ModeratorAssignment::where('content_id', $travsnap->id)
                ->where('content_type', 'travsnap')
                ->where('is_active', true)
                ->update(['is_active' => false]);
            
            // Update travsnap with new moderation_id
            $travsnap->update(['moderation_id' => $moderation->id]);

            return response()->json([
                'message' => 'Travsnap approved successfully', 
                'travsnap' => $travsnap
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in TravsnapController@approve', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Reject a travsnap (admin only).
     */    public function reject(Request $request, $id)
    {
        Log::info('TravsnapController@reject method called', ['id' => $id]);
        
        try {
            // Check if user is admin or super admin
            if (!Auth::user() || !in_array(Auth::user()->role, ['admin', 'superAdmin'])) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $request->validate([
                'notes' => 'required|string|max:500',
            ]);

            $travsnap = Travsnap::findOrFail($id);
              // Update travsnap status
            $travsnap->update(['status' => 'rejected']);
              // Create new moderation record
            $moderation = TravsnapModeration::create([
                'travsnap_id' => $travsnap->id,
                'moderator_id' => Auth::id(), // Current authenticated user as moderator
                'status' => 'rejected',
                'moderator_notes' => $request->input('notes'),
                'rejectionReason' => $request->input('notes'),
                'rejected_at' => now(),
                'is_active' => true,
            ]);
            
            // Update previous active moderation
            if ($travsnap->moderation_id) {
                TravsnapModeration::where('id', $travsnap->moderation_id)
                    ->update(['is_active' => false]);
            }
            
            // Deactivate moderator assignments for this travsnap
            \App\Models\ModeratorAssignment::where('content_id', $travsnap->id)
                ->where('content_type', 'travsnap')
                ->where('is_active', true)
                ->update(['is_active' => false]);
            // Update travsnap with new moderation_id
            $travsnap->update(['moderation_id' => $moderation->id]);

            return response()->json([
                'message' => 'Travsnap rejected successfully', 
                'travsnap' => $travsnap
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in TravsnapController@reject', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get featured travsnaps.
     */
    public function getFeatured()
    {
        Log::info('TravsnapController@getFeatured method called');
        
        try {
            $travsnaps = Travsnap::where('status', 'approved')
                ->where('is_active', true)
                ->where('is_featured', true)
                ->with('user:id,name,email,profile_photo')
                ->get();

            return response()->json([
                'message' => 'Featured travsnaps retrieved successfully', 
                'travsnaps' => $travsnaps
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in TravsnapController@getFeatured', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Toggle featured status (admin only).
     */
    public function toggleFeatured($id)
    {
        Log::info('TravsnapController@toggleFeatured method called', ['id' => $id]);
        
        try {
            // Check if user is admin
            if (!Auth::user() || !Auth::user()->is_admin) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $travsnap = Travsnap::findOrFail($id);
            $travsnap->update(['is_featured' => !$travsnap->is_featured]);

            return response()->json([
                'message' => 'Travsnap featured status updated successfully', 
                'is_featured' => $travsnap->is_featured
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in TravsnapController@toggleFeatured', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get user's travsnaps
     */
    public function getUserTravsnaps($userId)
    {
        Log::info('TravsnapController@getUserTravsnaps method called', ['userId' => $userId]);
        
        try {
            // Verify authorization (only allow admin or the user themselves)
            if (!Auth::user() || (Auth::id() != $userId && !Auth::user()->is_admin)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $travsnaps = Travsnap::where('user_id', $userId)
                ->where('is_active', true)
                ->get();

            return response()->json([
                'message' => 'User travsnaps retrieved successfully', 
                'travsnaps' => $travsnaps
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in TravsnapController@getUserTravsnaps', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }    // Helper function
    private function saveBase64Image($base64Image, $travsnap_id = null)
    {
        preg_match("/^data:image\/(.*?);base64,(.*)$/", $base64Image, $matches);
        $imageType = $matches[1];
        $imageData = base64_decode($matches[2]);

        $folderPath = $travsnap_id ? 'travsnap/' . $travsnap_id : 'travsnap';
        $filename = $folderPath . '/' . uniqid() . '.' . $imageType;
        Storage::disk('public')->put($filename, $imageData);
        return 'storage/' . $filename;
    }
      /**
     * Save a base64 encoded image to a specific folder
     *
     * @param string $base64Image Base64 encoded image
     * @param string $folder Folder path relative to storage/public
     * @param string $filenamePrefix Prefix for the filename
     * @return string The path to the saved image relative to the public directory
     */
    private function saveBase64ImageToFolder($base64Image, $folder, $filenamePrefix)
    {
        preg_match("/^data:image\/(.*?);base64,(.*)$/", $base64Image, $matches);
        $extension = $matches[1];
        $imageData = base64_decode($matches[2]);

        $filename = $folder . '/' . $filenamePrefix . '.' . $extension;
        Storage::disk('public')->put($filename, $imageData);

        return 'storage/' . $filename;
    }

    /**
     * Filter travsnaps based on various criteria
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function filter(Request $request)
    {
        Log::info('TravsnapController@filter method called');
        
        try {
            // Start with base query and immediately filter for approved travsnaps only
            $query = Travsnap::query()->where('status', 'approved')->where('is_active', true);

            // Get filter parameters from request body
            $title = $request->input('title');
            $location = $request->input('location');
            $userId = $request->input('user_id');
            $sortBy = $request->input('sort_by');
            
            // Log the filter parameters for debugging
            Log::info('Travsnap filter parameters', [
                'title' => $title,
                'location' => $location,
                'user_id' => $userId,
                'sort_by' => $sortBy,
            ]);
            
            // Apply title filter
            if ($title) {
                $query->where('title', 'like', '%' . $title . '%');
            }
            
            // Apply user filter
            if ($userId) {
                $query->where('user_id', $userId);
            }
            
            // Apply location filter - ROBUST VERSION
            if ($location && is_array($location)) {
                // Get all travsnaps first, then filter in PHP (for complex JSON handling)
                $tempQuery = clone $query;
                $allTravsnaps = $tempQuery->get();
                
                $filteredTravsnapIds = [];
                
                foreach ($allTravsnaps as $travsnap) {
                    try {
                        // Location is already cast as array in the model
                        $travsnapLocation = $travsnap->location;
                        
                        if (!$travsnapLocation) {
                            continue; // Skip if location is empty
                        }
                        
                        $matches = true;
                        
                        // Check each location criteria
                        foreach ($location as $key => $value) {
                            if (!empty($value) && (!isset($travsnapLocation[$key]) || $travsnapLocation[$key] !== $value)) {
                                $matches = false;
                                break;
                            }
                        }
                        
                        if ($matches) {
                            $filteredTravsnapIds[] = $travsnap->id;
                        }
                        
                    } catch (\Exception $e) {
                        // Log error and skip this travsnap
                        Log::warning('Error parsing travsnap location data', [
                            'travsnap_id' => $travsnap->id,
                            'location' => $travsnap->location,
                            'error' => $e->getMessage()
                        ]);
                        continue;
                    }
                }
                
                // Apply the filtered IDs to the query
                if (!empty($filteredTravsnapIds)) {
                    $query->whereIn('id', $filteredTravsnapIds);
                } else {
                    // No matches found, return empty result
                    $query->whereRaw('1 = 0'); // This will return no results
                }
                
                Log::info('Location filter applied via PHP', [
                    'location' => $location,
                    'filtered_travsnap_ids' => $filteredTravsnapIds,
                    'count' => count($filteredTravsnapIds)
                ]);            }
            
            // Eager load the user relationship with user details
            $query->with(['user.detail']);
            
            // Handle sorting options
            if ($sortBy) {
                // If sort_by is an array, handle multiple sorting criteria
                if (is_array($sortBy)) {
                    foreach ($sortBy as $sortOption) {
                        $this->applySorting($query, $sortOption);
                    }
                } else {
                    // Single sorting criterion
                    $this->applySorting($query, $sortBy);
                }
            } else {
                // Default ordering
                $query->latest();
            }
            
            // Get the filtered travsnaps
            $travsnaps = $query->get();
            
            // Log the final result
            Log::info('Travsnap filter result', [
                'count' => $travsnaps->count(),
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);
            
            // Transform the travsnaps to include user info and exclude moderation details
            $transformedTravsnaps = $travsnaps->map(function($travsnap) {
                // Create a new array with only the fields we want
                $travsnapData = $travsnap->toArray();
                
                // Remove the moderation related data if present
                if (isset($travsnapData['active_moderation'])) {
                    unset($travsnapData['active_moderation']);
                }
                  // Add user details in the required format
                if (isset($travsnapData['user'])) {
                    $userData = $travsnapData['user'];
                    $userDetail = $userData['detail'] ?? null;
                    
                    // Replace the user property with just the fields we need
                    $travsnapData['user'] = [
                        'id' => $userData['id'],
                        'name' => $userData['name'] ?? null,
                        'email' => $userData['email'] ?? null,
                        'first_name' => $userDetail['first_name'] ?? null,
                        'last_name' => $userDetail['last_name'] ?? null,
                        'profile_image_url' => $userDetail['profile_image_path'] ?? null
                    ];
                }
                
                return $travsnapData;
            });
            
            return response()->json([
                'message' => 'Travsnaps filtered successfully',
                'filters_applied' => [
                    'title' => $title,
                    'location' => $location,
                    'user_id' => $userId,
                    'sort_by' => $sortBy
                ],
                'count' => $travsnaps->count(),
                'travsnaps' => $transformedTravsnaps
            ]);
        } catch (\Exception $e) {
            Log::error('Error in TravsnapController@filter', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Apply sorting to the query based on the sort option
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $sortOption
     * @return void
     */
    private function applySorting($query, $sortOption)
    {
        switch ($sortOption) {
            case 'date':
                $query->orderBy('created_at', 'desc');
                break;
                
            case 'title':
                $query->orderBy('title', 'asc');
                break;
                
            case 'featured':
                $query->orderBy('is_featured', 'desc')->orderBy('created_at', 'desc');
                break;
                
            default:
                $query->latest();
                break;
        }
    }
}
