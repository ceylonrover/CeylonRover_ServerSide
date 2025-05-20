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

            // Save base64 image if present
            $imagePath = null;
            if (!empty($validated['image']) && str_starts_with($validated['image'], 'data:image')) {
                $imagePath = $this->saveBase64Image($validated['image']);
            }

            $blog = Blog::create([
                'title' => $validated['title'],
                'slug' => Str::slug($validated['title']),
                'description' => $validated['description'],
                'content' => $validated['content'],
                'user_id' => $validated['user_id'],
                //'author' => json_encode($validated['author'] ?? []),
                'categories' => json_encode($validated['categories']),
                'location' => json_encode($validated['location'] ?? []),
                'image' => $imagePath,
                'gallery' => json_encode($validated['gallery'] ?? []),
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
                'status' => 'published',
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

    // Helper function
    private function saveBase64Image($base64Image)
    {
        preg_match("/^data:image\/(.*?);base64,(.*)$/", $base64Image, $matches);
        $imageType = $matches[1];
        $imageData = base64_decode($matches[2]);

        $filename = 'blogs/' . uniqid() . '.' . $imageType;
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
                'author' => 'sometimes|string',
                'categories' => 'sometimes|array|min:1',
                'location' => 'nullable|array',
                'image' => 'nullable|string',
                'gallery' => 'nullable|array',
                'review' => 'nullable|string',
                'status' => 'sometimes|in:draft,published',
            ]);

            // Find the blog post by ID
            $blog = Blog::findOrFail($id);

            // Update the blog post with the validated data
            $blog->update([
                'title' => $validated['title'] ?? $blog->title,
                'slug' => isset($validated['title']) ? Str::slug($validated['title']) : $blog->slug,
                'description' => $validated['description'] ?? $blog->description,
                'additionalinfo' => $validated['additionalinfo'] ?? $blog->additionalinfo,
                'content' => $validated['content'] ?? $blog->content,
                'author' => $validated['author'] ?? $blog->author,
                'categories' => isset($validated['categories']) ? json_encode($validated['categories']) : $blog->categories,
                'location' => isset($validated['location']) ? json_encode($validated['location']) : $blog->location,
                'image' => $validated['image'] ?? $blog->image,
                'gallery' => isset($validated['gallery']) ? json_encode($validated['gallery']) : $blog->gallery,
                'review' => $validated['review'] ?? $blog->review,
                'status' => $validated['status'] ?? $blog->status,
            ]);

            // Return a success response
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
