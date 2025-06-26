<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\User;
use App\Models\BlogModeration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BlogModerationController extends Controller
{
    /**
     * Get a list of all pending blogs waiting for moderation
     * 
     * @return \Illuminate\Http\Response
     */
    public function getPendingBlogs()
    {
        \Log::info('Starting getPendingBlogs method');

        // Get blogs with status 'pending'
        $pendingBlogs = Blog::where('status', 'pending')
            ->with(['user:id,name,profile_image']) // Get limited user details
            ->select('id', 'title', 'created_at', 'image', 'user_id')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($pendingBlogs->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'No pending blogs found',
                'data' => []
            ]);
        }

        // Format the response data
        $formattedBlogs = $pendingBlogs->map(function ($blog) {
            return [
                'id' => $blog->id,
                'title' => $blog->title,
                'submitted_date' => $blog->created_at->format('Y-m-d H:i:s'),
                'main_image' => $blog->image,
                'user' => [
                    'id' => $blog->user->id,
                    'name' => $blog->user->name,
                    'profile_image' => $blog->user->profile_image ?? null,
                ]
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Pending blogs retrieved successfully',
            'data' => $formattedBlogs
        ]);
    }

    /**
     * Get a specific pending blog with more details
     * 
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function getPendingBlogDetails($id)
    {
        $blog = Blog::where('id', $id)
            ->where('status', 'pending')
            ->with(['user:id,name,email,profile_image'])
            ->first();

        if (!$blog) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pending blog not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pending blog details retrieved successfully',
            'data' => $blog
        ]);
    }    /**
     * Approve a pending blog
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function approveBlog(Request $request, $id)
    {
        
        $blog = Blog::findOrFail($id);

        if ($blog->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Blog is not in pending state'
            ], 400);
        }

        // Update blog status
        $blog->status = 'approved';
        $blog->save();

        // Find and update the existing moderation record
        $moderation = BlogModeration::where('blog_id', $blog->id)
            ->where('is_active', true)
            ->first();

        if ($moderation) {
            $moderation->update([
                'moderator_id' => auth()->id(),
                'status' => 'approved',
                'moderator_notes' => $request->input('notes', ''),
                'published_at' => now(),
            ]);
        } else {
            // If no active moderation record exists, create a new one
            BlogModeration::create([
                'blog_id' => $blog->id,
                'moderator_id' => auth()->id(),
                'status' => 'approved',
                'moderator_notes' => $request->input('notes', ''),
                'published_at' => now(),
                'is_active' => true,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Blog approved successfully'
        ]);
    }    /**
     * Reject a pending blog
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */    public function rejectBlog(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $blog = Blog::findOrFail($id);

        if ($blog->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Blog is not in pending state'
            ], 400);
        }

        // Update blog status
        $blog->status = 'rejected';
        $blog->save();

        // Find and update the existing moderation record
        $moderation = BlogModeration::where('blog_id', $blog->id)
            ->where('is_active', true)
            ->first();

        if ($moderation) {            $moderation->update([
                'moderator_id' => auth()->id(),
                'status' => 'rejected',
                'rejectionReason' => $request->input('rejection_reason', ''),
                'moderator_notes' => $request->input('notes', ''),
                'rejected_at' => now(),
            ]);
        } else {
            // If no active moderation record exists, create a new one
            BlogModeration::create([
                'blog_id' => $blog->id,
                'moderator_id' => auth()->id(),
                'status' => 'rejected',
                'rejectionReason' => $request->input('rejection_reason', ''),
                'moderator_notes' => $request->input('notes', ''),
                'rejected_at' => now(),
                'is_active' => true,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Blog rejected successfully'
        ]);
    }

    /**
     * Get a list of all approved blogs
     * 
     * @return \Illuminate\Http\Response
     */    public function getApprovedBlogs()
    {
        \Log::info('Starting getApprovedBlogs method');        // Get blogs with status 'approved'

        $approvedBlogs = Blog::where('status', 'approved')
            ->with(['user:id,name,profile_image']) // Get limited user details
            ->select('id', 'title', 'created_at', 'updated_at', 'image', 'user_id')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($approvedBlogs->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'No approved blogs found',
                'data' => []
            ]);
        }        // Format the response data
        $formattedBlogs = $approvedBlogs->map(function ($blog) {            return [
                'id' => $blog->id,
                'title' => $blog->title,
                'submitted_date' => $blog->created_at ? $blog->created_at->format('Y-m-d H:i:s') : null,
                'approved_date' => $blog->updated_at ? $blog->updated_at->format('Y-m-d H:i:s') : null,
                'main_image' => $blog->image,
                'user' => [
                    'id' => $blog->user->id,
                    'name' => $blog->user->name,
                    'profile_image' => $blog->user->profile_image ?? null,
                ]
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Approved blogs retrieved successfully',
            'data' => $formattedBlogs
        ]);
    }

    /**
     * Get a list of all rejected blogs
     * 
     * @return \Illuminate\Http\Response
     */    public function getRejectedBlogs()
    {
        \Log::info('Starting getRejectedBlogs method');

        // Get blogs with status 'rejected'
        $rejectedBlogs = Blog::where('status', 'rejected')
            ->with(['user:id,name,profile_image']) // Get limited user details
            ->select('id', 'title', 'created_at', 'updated_at', 'image', 'user_id')
            ->orderBy('updated_at', 'desc')
            ->get();

        if ($rejectedBlogs->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'No rejected blogs found',
                'data' => []
            ]);
        }

        // Format the response data
        $formattedBlogs = $rejectedBlogs->map(function ($blog) {
            // Get rejection details from moderation record
            $moderation = BlogModeration::where('blog_id', $blog->id)
                ->where('status', 'rejected')
                ->where('is_active', true)
                ->first();            return [
                'id' => $blog->id,
                'title' => $blog->title,
                'submitted_date' => $blog->created_at ? $blog->created_at->format('Y-m-d H:i:s') : null,
                'rejected_date' => $blog->updated_at ? $blog->updated_at->format('Y-m-d H:i:s') : null,
                'main_image' => $blog->image,
                'rejection_reason' => $moderation ? $moderation->rejectionReason : '',
                'user' => [
                    'id' => $blog->user->id,
                    'name' => $blog->user->name,
                    'profile_image' => $blog->user->profile_image ?? null,
                ]
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Rejected blogs retrieved successfully',
            'data' => $formattedBlogs
        ]);
    }

    /**
     * Get a specific approved blog with more details
     * 
     * @param int $id
     * @return \Illuminate\Http\Response
     */    public function getApprovedBlogDetails($id)
    {
        \Log::info('Starting getApprovedBlogDetails method', ['id' => $id]);

        $blog = Blog::where('id', $id)
            ->where('status', 'approved')
            ->with(['user:id,name,email,profile_image'])
            ->first();

        if (!$blog) {
            return response()->json([
                'status' => 'error',
                'message' => 'Approved blog not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Approved blog details retrieved successfully',
            'data' => $blog
        ]);
    }

    /**
     * Get a specific rejected blog with more details
     * 
     * @param int $id
     * @return \Illuminate\Http\Response
     */    public function getRejectedBlogDetails($id)
    {
        \Log::info('Starting getRejectedBlogDetails method', ['id' => $id]);

        $blog = Blog::where('id', $id)
            ->where('status', 'rejected')
            ->with(['user:id,name,email,profile_image'])
            ->first();

        if (!$blog) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rejected blog not found'
            ], 404);
        }

        // Get rejection details from moderation record
        $moderation = BlogModeration::where('blog_id', $blog->id)
            ->where('status', 'rejected')
            ->where('is_active', true)
            ->first();

        // Add rejection details to the blog data
        $blogData = $blog->toArray();
        $blogData['rejection_details'] = [
            'reason' => $moderation ? $moderation->rejectionReason : '',
            'notes' => $moderation ? $moderation->moderator_notes : '',
            'rejected_at' => $moderation && $moderation->rejected_at ? $moderation->rejected_at->format('Y-m-d H:i:s') : null,
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Rejected blog details retrieved successfully',
            'data' => $blogData
        ]);
    }
}
