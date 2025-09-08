<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Blog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;

class BlogController extends Controller
{
    public function store(Request $request)
    {
        Log::info('BlogController@store method called', ['ip' => $request->ip()]);

        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'content' => 'required|string',
                'user_id' => 'required|integer|exists:users,id',
                'categories' => 'required|array|min:1',
                'location' => 'nullable|array',
                'image' => 'nullable|string',
                'gallery' => 'nullable|array',
                'operatingHours' => 'nullable|string',
                'entryFee' => 'nullable|string',
                'suitableFor' => 'nullable|array',
                'specialty' => 'nullable|string',
                'closedDates' => 'nullable|string',
                'routeDetails' => 'nullable|string',
                'safetyMeasures' => 'nullable|string',
                'restrictions' => 'nullable|string',
                'climate' => 'nullable|string',
                'travelAdvice' => 'nullable|string',
                'emergencyContacts' => 'nullable|string',
                'assistance' => 'nullable|string',
                'type' => 'nullable|string',
                'views' => 'nullable|integer',
            ]);

            // Generate a unique slug
            $baseSlug = Str::slug($validated['title']);
            $slug = $baseSlug;
            $counter = 1;
            
            // Check if the slug exists, if so, append a number
            while (Blog::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter++;
            }

            // Create blog first to get ID
            $blog = Blog::create([
                'title' => $validated['title'],
                'slug' => $slug, // Use the unique slug
                'description' => $validated['description'],
                'content' => $validated['content'],
                'user_id' => $validated['user_id'],
                'categories' => json_encode($validated['categories']),
                'location' => json_encode($validated['location'] ?? []),
                'image' => null, // Will update after saving
                'gallery' => json_encode([]), // Will update after saving
                'operatingHours' => $validated['operatingHours'] ?? '',
                'entryFee' => $validated['entryFee'] ?? '',
                'suitableFor' => json_encode($validated['suitableFor'] ?? []),
                'specialty' => $validated['specialty'] ?? '',
                'closedDates' => $validated['closedDates'] ?? '',
                'routeDetails' => $validated['routeDetails'] ?? '',
                'safetyMeasures' => $validated['safetyMeasures'] ?? '',
                'restrictions' => $validated['restrictions'] ?? '',
                'climate' => $validated['climate'] ?? '',
                'travelAdvice' => $validated['travelAdvice'] ?? '',
                'emergencyContacts' => $validated['emergencyContacts'] ?? '',
                'assistance' => $validated['assistance'] ?? '',
                'type' => $validated['type'] ?? 'General',
                'views' => $validated['views'] ?? 0,
                'status' => 'pending', // default status
            ]);

            // Create folder using blog ID
            $folderPath = 'blog/' . $blog->id;

            // Save main image
            $mainImagePath = null;
            if (!empty($validated['image']) && str_starts_with($validated['image'], 'data:image')) {
                $mainImagePath = $this->saveBase64ImageToFolder($validated['image'], $folderPath, 'main');
            }

            // Save gallery images
            $galleryImagePaths = [];
            if (!empty($validated['gallery']) && is_array($validated['gallery'])) {
                foreach ($validated['gallery'] as $index => $base64Image) {
                    if (str_starts_with($base64Image, 'data:image')) {
                        $galleryImagePaths[] = $this->saveBase64ImageToFolder($base64Image, $folderPath, 'img' . ($index + 1));
                    }
                }
            }

            // Update blog with image paths
            $blog->update([
                'image' => $mainImagePath,
                'gallery' => json_encode($galleryImagePaths)
            ]);            // Create moderation record
            $moderation = \App\Models\BlogModeration::create([
                'blog_id' => $blog->id,
                'moderator_id' => $validated['user_id'], // Set creator as initial moderator
                'status' => 'pending',
                'moderator_notes' => null,
                'is_active' => true,
            ]);              // If there's a super admin in the system, automatically assign them to moderate this blog
            $superAdmin = \App\Models\User::where('role', 'superAdmin')->first();
            if ($superAdmin) {
                \App\Models\ModeratorAssignment::create([
                    'moderator_id' => $superAdmin->id,
                    'content_id' => $blog->id,
                    'content_type' => 'blog',
                    'is_active' => true
                ]);
                
                // Notify only super admin
                Mail::send('emails.blog-notification-admin', ['blog' => $blog], function ($message) use ($superAdmin, $blog) {
                    $message->to($superAdmin->email)
                            ->subject("New Blog Post Submitted: {$blog->title}");
                });
            }

            return response()->json(['message' => 'Blog created successfully', 'blog' => $blog], 201);
        } catch (\Exception $e) {
            Log::error('Error in BlogController@store', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }

    private function saveBase64ImageToFolder($base64Image, $folder, $filenamePrefix)
    {
        preg_match("/^data:image\/(.*?);base64,(.*)$/", $base64Image, $matches);
        $extension = $matches[1];
        $imageData = base64_decode($matches[2]);

        $filename = $folder . '/' . $filenamePrefix . '.' . $extension;
        Storage::disk('public')->put($filename, $imageData);

        return 'storage/' . $filename;
    }    //Get All Posts
    public function getAllPosts()
    {
        Log::info('BlogController@getAllPosts method called');
        
        try {
            // Get all blogs regardless of status
            $blogs = Blog::all();

            return response()->json(['message' => 'Blogs retrieved successfully', 'blogs' => $blogs], 200);
        } catch (\Exception $e) {
            Log::error('Error in BlogController@getAllPosts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }

    public function getAll(Request $request)
    {
        if ($request->user() && $request->user()->is_admin) {
            return Blog::all(); // All including pending and rejected
        }

        return Blog::where('status', 'approved')->get(); // Public users
    }
    
    public function update(Request $request, $id)
    {
        Log::info('BlogController@update method called', ['id' => $id, 'ip' => $request->ip()]);

        try {
            // Validate the request data
            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'additionalinfo' => 'nullable|string',
                'content' => 'sometimes|string',
                'user_id' => 'sometimes|integer|exists:users,id',
                'categories' => 'sometimes|array|min:1',
                'location' => 'nullable|array',
                'image' => 'nullable|file|image|max:2048',
                'gallery.*' => 'nullable|file|image|max:2048',
                'review' => 'nullable|string',
                'operatingHours' => 'nullable|string',
                'entryFee' => 'nullable|string',
                'suitableFor' => 'nullable|array',
                'specialty' => 'nullable|string',
                'closedDates' => 'nullable|string',
                'routeDetails' => 'nullable|string',
                'safetyMeasures' => 'nullable|string',
                'restrictions' => 'nullable|string',
                'climate' => 'nullable|string',
                'travelAdvice' => 'nullable|string',
                'emergencyContacts' => 'nullable|string',
                'assistance' => 'nullable|string',
                'type' => 'nullable|string',
                'views' => 'nullable|integer',
                'status' => 'sometimes|in:draft,published,pending,approved,rejected',
            ]);

            // Find the blog
            $blog = Blog::findOrFail($id);

            // Use blog ID for folder path
            $folder = 'blog/' . $blog->id;

            // Main image upload
            if ($request->hasFile('image')) {
                $imageFile = $request->file('image');
                $filename = 'main.' . $imageFile->getClientOriginalExtension();
                $imagePath = $imageFile->storeAs($folder, $filename, 'public');
                $validated['image'] = 'storage/' . $imagePath;
            }

            // Gallery image upload
            $galleryPaths = json_decode($blog->gallery, true) ?? [];
            if ($request->hasFile('gallery')) {
                foreach ($request->file('gallery') as $index => $galleryImage) {
                    $filename = 'img' . (count($galleryPaths) + $index + 1) . '.' . $galleryImage->getClientOriginalExtension();
                    $path = $galleryImage->storeAs($folder, $filename, 'public');
                    $galleryPaths[] = 'storage/' . $path;
                }
                $validated['gallery'] = json_encode($galleryPaths);
            }

            // Other JSON fields
            if (isset($validated['categories'])) {
                $validated['categories'] = json_encode($validated['categories']);
            }
            if (isset($validated['location'])) {
                $validated['location'] = json_encode($validated['location']);
            }
            if (isset($validated['suitableFor'])) {
                $validated['suitableFor'] = json_encode($validated['suitableFor']);
            }

            // Set slug if title changed
            if (isset($validated['title'])) {
                $validated['slug'] = Str::slug($validated['title']);
            }

            // Update blog
            $blog->update($validated);

            // Create new moderation record if status changed
            if (isset($validated['status']) && $validated['status'] != $blog->getOriginal('status')) {
                // Set existing moderation records to inactive
                \App\Models\BlogModeration::where('blog_id', $blog->id)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
                
                // Create new moderation record
                $moderation = \App\Models\BlogModeration::create([
                    'blog_id' => $blog->id,
                    'moderator_id' => $request->user()->id ?? $blog->user_id,
                    'status' => $validated['status'],
                    'moderator_notes' => $request->input('moderator_notes'),
                    'is_active' => true,
                    'published_at' => $validated['status'] === 'approved' ? now() : null,
                    'rejected_at' => $validated['status'] === 'rejected' ? now() : null,
                ]);
            }
            
            return response()->json(['message' => 'Blog updated successfully', 'blog' => $blog], 200);
        } catch (\Exception $e) {

            Log::error('Error in BlogController@update', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'id' => $id
            ]);
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    } 
    

public function filter(Request $request)
{
    // Start with base query and immediately filter for approved blogs only
    $query = \App\Models\Blog::query()->where('blogs.status', 'approved');

    // Get filter parameters from request body
    $title = $request->input('title');
    $author = $request->input('author');
    $categories = $request->input('categories');
    $sortBy = $request->input('sort_by');
    $location = $request->input('location');
    
    // Log the filter parameters for debugging
    \Illuminate\Support\Facades\Log::info('Blog filter parameters', [
        'title' => $title,
        'author' => $author,
        'categories' => $categories,
        'sort_by' => $sortBy,
        'location' => $location,
    ]);
    
    // Apply title filter
    if ($title) {
        $query->where('title', 'like', '%' . $title . '%');
    }
    
    // Apply author filter
    if ($author) {
        $query->where('author', 'like', '%' . $author . '%');
    }
    
    // Apply categories filter (handle multiple categories)
    if ($categories && is_array($categories)) {
        $query->where(function($query) use ($categories) {
            foreach ($categories as $category) {
                // Handle double-encoded JSON with escaped quotes (\\\\\")
                $query->orWhere('categories', 'LIKE', '%\\\\\"' . $category . '\\\\\"%');
            }
        });
    }
    
    // Apply location filter - ALTERNATIVE ROBUST VERSION
    if ($location && is_array($location)) {
        // Get all blogs first, then filter in PHP (for complex JSON handling)
        $tempQuery = clone $query;
        $allBlogs = $tempQuery->get();
        
        $filteredBlogIds = [];
        
        foreach ($allBlogs as $blog) {
            try {
                // Decode the JSON location string
                $blogLocation = json_decode($blog->location, true);
                
                if (!$blogLocation) {
                    continue; // Skip if JSON is invalid
                }
                
                $matches = true;
                
                // Check each location criteria
                foreach ($location as $key => $value) {
                    if (!empty($value) && (!isset($blogLocation[$key]) || $blogLocation[$key] !== $value)) {
                        $matches = false;
                        break;
                    }
                }
                
                if ($matches) {
                    $filteredBlogIds[] = $blog->id;
                }
                
            } catch (\Exception $e) {
                // Log error and skip this blog
                \Illuminate\Support\Facades\Log::warning('Error parsing blog location JSON', [
                    'blog_id' => $blog->id,
                    'location' => $blog->location,
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }
        
        // Apply the filtered IDs to the query
        if (!empty($filteredBlogIds)) {
            $query->whereIn('id', $filteredBlogIds);
        } else {
            // No matches found, return empty result
            $query->whereRaw('1 = 0'); // This will return no results
        }
        
        \Illuminate\Support\Facades\Log::info('Location filter applied via PHP', [
            'location' => $location,
            'filtered_blog_ids' => $filteredBlogIds,
            'count' => count($filteredBlogIds)
        ]);
    }
    
    // Count likes from the likes table
    $query->withCount('likedBy as likes_count');
    
    // Eager load the user relationship with specific fields and the user's details
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
    
    // Get the filtered blogs
    $blogs = $query->get();
    
    // Log the final result
    \Illuminate\Support\Facades\Log::info('Blog filter result', [
        'count' => $blogs->count(),
        'sql' => $query->toSql(),
        'bindings' => $query->getBindings()
    ]);
    
    // Transform the blogs to include user info, likes count, and exclude latestModeration
    $transformedBlogs = $blogs->map(function($blog) {
        // Create a new array with only the fields we want
        $blogData = $blog->toArray();
        
        // Remove the latestModeration relationship
        if (isset($blogData['latest_moderation'])) {
            unset($blogData['latest_moderation']);
        }
        
        // Ensure likes_count is included in the response
        $blogData['likes_count'] = $blog->likes_count ?? 0;
        
        // Add user details in the required format
        if (isset($blogData['user']) && isset($blogData['user']['detail'])) {
            $userDetail = $blogData['user']['detail'];
            
            // Replace the user property with just the fields we need
            $blogData['user'] = [
                'id' => $blogData['user']['id'],
                'first_name' => $userDetail['first_name'] ?? null,
                'last_name' => $userDetail['last_name'] ?? null,
                'profile_image_url' => $userDetail['profile_image_path'] ?? null
            ];
        }
        
        return $blogData;
    });
    
    return response()->json([
        'message' => 'Blogs filtered successfully',
        'filters_applied' => [
            'title' => $title,
            'author' => $author,
            'categories' => $categories,
            'location' => $location,
            'sort_by' => $sortBy
        ],
        'count' => $blogs->count(),
        'blogs' => $transformedBlogs
    ]);
}
    public function search(Request $request)
    {
        $search = $request->input('q');

        $blogs = \App\Models\Blog::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%")
                    ->orWhere('content', 'like', "%$search%")
                    ->orWhere('author', 'like', "%$search%")
                    ->orWhereJsonContains('categories', $search);
                });
            })
            ->latest()
            ->get();

        return response()->json($blogs);
    }
    
    /**
     * Get a specific blog by ID
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */    public function show($id)
    {
        Log::info('BlogController@show method called', ['id' => $id]);
        
        try {
            // Get the blog regardless of status
            $blog = Blog::with(['user', 'latestModeration'])->findOrFail($id);
            
            // Increment the view counter
            $blog->increment('views');
            
            return response()->json([
                'message' => 'Blog retrieved successfully', 
                'blog' => $blog
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Blog not found', ['id' => $id]);
            return response()->json(['message' => 'Blog not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error in BlogController@show', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'id' => $id
            ]);
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }

    public function approve($id)
    {
        try {
            $blog = Blog::findOrFail($id);
            $blog->status = 'approved';
            $blog->save();

            // Set existing moderation records to inactive
            \App\Models\BlogModeration::where('blog_id', $blog->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
            
            // Create new moderation record
            $moderation = \App\Models\BlogModeration::create([
                'blog_id' => $blog->id,
                'moderator_id' => auth()->id(), // Current authenticated user as moderator
                'status' => 'approved',
                'moderator_notes' => 'Approved by admin',
                'published_at' => now(),
                'is_active' => true,
            ]);
            
            // Deactivate moderator assignments for this blog
            \App\Models\ModeratorAssignment::where('content_id', $blog->id)
                ->where('content_type', 'blog')
                ->where('is_active', true)
                ->update(['is_active' => false]);

            return response()->json(['message' => 'Blog approved']);
        } catch (\Exception $e) {
            Log::error('Error in BlogController@approve', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'id' => $id
            ]);
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }    public function reject(Request $request, $id)
    {
        try {
            $request->validate([
                'rejectionReason' => 'required|string|max:500',
            ]);

            $blog = Blog::findOrFail($id);
            $blog->status = 'rejected';
            $blog->save();

            // Set existing moderation records to inactive
            \App\Models\BlogModeration::where('blog_id', $blog->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
            
            // Create new moderation record
            $moderation = \App\Models\BlogModeration::create([
                'blog_id' => $blog->id,
                'moderator_id' => auth()->id(), // Current authenticated user as moderator
                'status' => 'rejected',
                'moderator_notes' => $request->input('rejectionReason'),
                'rejectionReason' => $request->input('rejectionReason'),
                'rejected_at' => now(),
                'is_active' => true,
            ]);
            
            // Deactivate moderator assignments for this blog
            \App\Models\ModeratorAssignment::where('content_id', $blog->id)
                ->where('content_type', 'blog')
                ->where('is_active', true)
                ->update(['is_active' => false]);

            return response()->json(['message' => 'Blog rejected']);
        } catch (\Exception $e) {
            Log::error('Error in BlogController@reject', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'id' => $id
            ]);
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }    public function getPending(Request $request)
    {
        // Check if user is admin or super admin
        if (!in_array($request->user()->role, ['admin', 'superAdmin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // If super admin, get all pending blogs
        if ($request->user()->role === 'superAdmin') {
            $pendingBlogs = Blog::where('status', 'pending')->get();
            return response()->json($pendingBlogs);
        }
        
        // For regular admins, get only blogs assigned to them
        $assignedBlogIds = \App\Models\ModeratorAssignment::where('moderator_id', $request->user()->id)
            ->where('content_type', 'blog')
            ->where('is_active', true)
            ->pluck('content_id')
            ->toArray();
            
        $pendingBlogs = Blog::where('status', 'pending')
            ->whereIn('id', $assignedBlogIds)
            ->get();
            
        return response()->json($pendingBlogs);
    }    /**
     * Apply sorting to the query based on the sort option
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $sortOption
     * @return void
     */    
    private function applySorting($query, $sortOption)
    {
        switch ($sortOption) {
            case 'likes':
                $query->orderBy('likes_count', 'desc');
                break;
                
            case 'date':
                // For date sorting, we'll use created_at instead of joining with blog_moderations
                $query->orderBy('blogs.created_at', 'desc');
                break;
                
            case 'views':
                $query->orderBy('views', 'desc');
                break;
                
            default:
                $query->latest('blogs.created_at');
                break;
        }
    }
    
    /**
     * Get sample data from the database to understand location structure
     * This is a development/debug endpoint only
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLocationSamples(Request $request)
    {
        try {
            // Get a few blogs with non-empty location data
            $blogsWithLocation = Blog::whereNotNull('location')
                ->where('location', '<>', '[]')
                ->where('location', '<>', '{}')
                ->where('location', '<>', 'null')
                ->limit(10)
                ->get(['id', 'title', 'location']);
            
            // Get the raw location data for analysis
            $locationSamples = $blogsWithLocation->map(function($blog) {
                return [
                    'id' => $blog->id,
                    'title' => $blog->title,
                    'location_raw' => $blog->getRawOriginal('location'),
                    'location_parsed' => $blog->location
                ];
            });
            
            return response()->json([
                'message' => 'Location samples retrieved',
                'samples' => $locationSamples,
                'count' => $locationSamples->count()
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error in BlogController@getLocationSamples', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }
}
