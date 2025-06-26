<?php


namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\ModeratorAssignment;
use App\Models\Travsnap;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ModeratorAssignmentController extends Controller
{
    /**
     * Assign a moderator to content
     */    public function assign(Request $request)
    {
        try {
            // Verify user is super admin
            if (!Auth::user() || Auth::user()->role !== 'superAdmin') {
                return response()->json(['message' => 'Unauthorized. Only super admins can assign moderators.'], 403);
            }

            $validated = $request->validate([
                'moderator_id' => 'required|exists:users,id',
                'content_id' => 'required|integer',
                'content_type' => 'required|in:blog,travsnap',
            ]);

            // Verify moderator is an admin
            $moderator = User::findOrFail($validated['moderator_id']);
            if ($moderator->role !== 'admin' && $moderator->role !== 'superAdmin') {
                return response()->json(['message' => 'Selected user is not an admin'], 400);
            }

            // Verify content exists
            if ($validated['content_type'] === 'blog') {
                $content = Blog::findOrFail($validated['content_id']);
            } else {
                $content = Travsnap::findOrFail($validated['content_id']);
            }

            // Deactivate any existing assignments
            ModeratorAssignment::where('content_id', $validated['content_id'])
                ->where('content_type', $validated['content_type'])
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // Create new assignment
            $assignment = ModeratorAssignment::create([
                'moderator_id' => $validated['moderator_id'],
                'content_id' => $validated['content_id'],
                'content_type' => $validated['content_type'],
                'is_active' => true,
            ]);

            return response()->json([
                'message' => 'Moderator assigned successfully', 
                'assignment' => $assignment
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error in ModeratorAssignmentController@assign', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all pending content for a moderator
     */
    public function getModeratorAssignments($moderatorId = null)
    {
        try {
            // If no moderator ID provided, use current user            $moderatorId = $moderatorId ?? Auth::id();

            // Check authorization
            if (Auth::id() != $moderatorId && Auth::user()->role !== 'superAdmin') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Get active assignments
            $assignments = ModeratorAssignment::where('moderator_id', $moderatorId)
                ->where('is_active', true)
                ->get();

            $pendingContent = [
                'blogs' => [],
                'travsnaps' => []
            ];

            // Get assigned content details
            foreach ($assignments as $assignment) {
                if ($assignment->content_type === 'blog') {
                    $blog = Blog::where('id', $assignment->content_id)
                        ->where('status', 'pending')
                        ->first();
                    
                    if ($blog) {
                        $pendingContent['blogs'][] = $blog;
                    }
                } else {
                    $travsnap = Travsnap::where('id', $assignment->content_id)
                        ->where('status', 'pending')
                        ->first();
                    
                    if ($travsnap) {
                        $pendingContent['travsnaps'][] = $travsnap;
                    }
                }
            }

            return response()->json([
                'message' => 'Moderator assignments retrieved successfully',
                'pendingContent' => $pendingContent
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in ModeratorAssignmentController@getModeratorAssignments', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all pending unassigned content (for super admin)
     */    public function getUnassignedContent()
    {
        try {
            // Only super admin can see all unassigned content
            if (Auth::user()->role !== 'superAdmin') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Get all assigned content IDs
            $assignedBlogIds = ModeratorAssignment::where('content_type', 'blog')
                ->where('is_active', true)
                ->pluck('content_id')
                ->toArray();

            $assignedTravsnapIds = ModeratorAssignment::where('content_type', 'travsnap')
                ->where('is_active', true)
                ->pluck('content_id')
                ->toArray();

            // Get unassigned pending content
            $unassignedBlogs = Blog::where('status', 'pending')
                ->whereNotIn('id', $assignedBlogIds)
                ->get();

            $unassignedTravsnaps = Travsnap::where('status', 'pending')
                ->whereNotIn('id', $assignedTravsnapIds)
                ->get();

            return response()->json([
                'message' => 'Unassigned content retrieved successfully',
                'unassignedContent' => [
                    'blogs' => $unassignedBlogs,
                    'travsnaps' => $unassignedTravsnaps
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in ModeratorAssignmentController@getUnassignedContent', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get available admin moderators
     */    public function getAvailableModerators()
    {
        try {
            // Only super admin can see all admins
            if (Auth::user()->role !== 'superAdmin') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Get all admin users
            $admins = User::whereIn('role', ['admin', 'superAdmin'])
                ->select('id', 'name', 'email', 'role')
                ->get();

            return response()->json([
                'message' => 'Available moderators retrieved successfully',
                'moderators' => $admins
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in ModeratorAssignmentController@getAvailableModerators', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }
}