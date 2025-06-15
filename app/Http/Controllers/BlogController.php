<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Blog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

            // Create folder name from title slug
            $slug = Str::slug($validated['title']);
            $folderPath = 'blogs/' . $slug;

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

            // Create blog
            $blog = Blog::create([
                'title' => $validated['title'],
                'slug' => $slug,
                'description' => $validated['description'],
                'content' => $validated['content'],
                'user_id' => $validated['user_id'],
                'categories' => json_encode($validated['categories']),
                'location' => json_encode($validated['location'] ?? []),
                'image' => $mainImagePath,
                'gallery' => json_encode($galleryImagePaths),
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
    }

    //Get All Posts
    public function getAllPosts()
    {
        Log::info('BlogController@getAllPosts method called');
        
        try {
            $blogs = Blog::where('status', 'approved')->get();

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

            // Validate the request data
            $blog = Blog::findOrFail($id);

            $slug = isset($validated['title']) ? Str::slug($validated['title']) : $blog->slug;
            $folder = 'blogs/' . $slug;

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

            // Set slug
            if (isset($validated['title'])) {
                $validated['slug'] = $slug;
            }

            $blog->update($validated);

            
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
        $query = \App\Models\Blog::query();

        if ($request->has('title')) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }

        if ($request->has('author')) {
            $query->where('author', 'like', '%' . $request->author . '%');
        }

        if ($request->has('category')) {
            $query->whereJsonContains('categories', $request->category);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->latest()->get());
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

    public function approve($id)
    {
        $blog = Blog::findOrFail($id);
        $blog->status = 'approved';
        $blog->save();

        return response()->json(['message' => 'Blog approved']);
    }

    public function reject($id)
    {
        $blog = Blog::findOrFail($id);
        $blog->status = 'rejected';
        $blog->save();

        return response()->json(['message' => 'Blog rejected']);
    }

    public function getPending(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $pendingBlogs = Blog::where('status', 'pending')->get();
        return response()->json($pendingBlogs);
    }
}
