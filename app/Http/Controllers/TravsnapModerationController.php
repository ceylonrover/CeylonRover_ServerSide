<?php

namespace App\Http\Controllers;

use App\Models\Travsnap;
use App\Models\User;
use App\Models\TravsnapModeration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TravsnapModerationController extends Controller
{
    /**
     * Get a list of all pending travsnaps waiting for moderation
     * 
     * @return \Illuminate\Http\Response
     */
    public function getPendingTravsnaps()
    {
        \Log::info('Starting getPendingTravsnaps method');        // Get travsnaps with status 'pending'
        $pendingTravsnaps = Travsnap::where('status', 'pending')
            ->with(['user:id,name,profile_image']) // Get limited user details
            ->select('id', 'title', 'created_at', 'gallery', 'user_id', 'location', 'description')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($pendingTravsnaps->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'No pending travsnaps found',
                'data' => []
            ]);
        }        // Format the response data
        $formattedTravsnaps = $pendingTravsnaps->map(function ($travsnap) {
            $galleryImages = [];
            if (is_array($travsnap->gallery) && !empty($travsnap->gallery)) {
                $galleryImages = $travsnap->gallery;
            }
              return [
                'id' => $travsnap->id,
                'title' => $travsnap->title,
                'description' => $travsnap->description,
                'location' => $travsnap->location,
                'submitted_date' => $travsnap->created_at->format('Y-m-d H:i:s'),
                'gallery' => $galleryImages,
                'user' => [
                    'id' => $travsnap->user->id,
                    'name' => $travsnap->user->name,
                    'profile_image' => $travsnap->user->profile_image ?? null,
                ]
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Pending travsnaps retrieved successfully',
            'data' => $formattedTravsnaps
        ]);
    }

    /**
     * Get a specific pending travsnap with more details
     * 
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function getPendingTravsnapDetails($id)
    {
        $travsnap = Travsnap::where('id', $id)
            ->where('status', 'pending')
            ->with(['user:id,name,email,profile_image'])
            ->first();

        if (!$travsnap) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pending travsnap not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pending travsnap details retrieved successfully',
            'data' => $travsnap
        ]);
    }

    /**
     * Approve a pending travsnap
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function approveTravsnap(Request $request, $id)
    {
        $travsnap = Travsnap::findOrFail($id);

        if ($travsnap->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Travsnap is not in pending state'
            ], 400);
        }

        // Update travsnap status
        $travsnap->status = 'approved';
        $travsnap->save();

        // Find and update the existing moderation record
        $moderation = TravsnapModeration::where('travsnap_id', $travsnap->id)
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
            TravsnapModeration::create([
                'travsnap_id' => $travsnap->id,
                'moderator_id' => auth()->id(),
                'status' => 'approved',
                'moderator_notes' => $request->input('notes', ''),
                'published_at' => now(),
                'is_active' => true,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Travsnap approved successfully'
        ]);
    }

    /**
     * Reject a pending travsnap
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function rejectTravsnap(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $travsnap = Travsnap::findOrFail($id);

        if ($travsnap->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Travsnap is not in pending state'
            ], 400);
        }

        // Update travsnap status
        $travsnap->status = 'rejected';
        $travsnap->save();

        // Find and update the existing moderation record
        $moderation = TravsnapModeration::where('travsnap_id', $travsnap->id)
            ->where('is_active', true)
            ->first();

        if ($moderation) {
            $moderation->update([
                'moderator_id' => auth()->id(),
                'status' => 'rejected',
                'rejectionReason' => $request->input('rejection_reason', ''),
                'moderator_notes' => $request->input('notes', ''),
                'rejected_at' => now(),
            ]);
        } else {
            // If no active moderation record exists, create a new one
            TravsnapModeration::create([
                'travsnap_id' => $travsnap->id,
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
            'message' => 'Travsnap rejected successfully'
        ]);
    }

    /**
     * Get a list of all approved travsnaps
     * 
     * @return \Illuminate\Http\Response
     */
    public function getApprovedTravsnaps()
    {
        \Log::info('Starting getApprovedTravsnaps method');        // Get travsnaps with status 'approved'
        $approvedTravsnaps = Travsnap::where('status', 'approved')
            ->with(['user:id,name,profile_image']) // Get limited user details
            ->select('id', 'title', 'created_at', 'updated_at', 'gallery', 'user_id', 'location', 'description')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($approvedTravsnaps->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'No approved travsnaps found',
                'data' => []
            ]);
        }        // Format the response data
        $formattedTravsnaps = $approvedTravsnaps->map(function ($travsnap) {
            $galleryImages = [];
            if (is_array($travsnap->gallery) && !empty($travsnap->gallery)) {
                $galleryImages = $travsnap->gallery;
            }
              return [
                'id' => $travsnap->id,
                'title' => $travsnap->title,
                'description' => $travsnap->description,
                'location' => $travsnap->location,
                'submitted_date' => $travsnap->created_at ? $travsnap->created_at->format('Y-m-d H:i:s') : null,
                'approved_date' => $travsnap->updated_at ? $travsnap->updated_at->format('Y-m-d H:i:s') : null,
                'gallery' => $galleryImages,
                'user' => [
                    'id' => $travsnap->user->id,
                    'name' => $travsnap->user->name,
                    'profile_image' => $travsnap->user->profile_image ?? null,
                ]
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Approved travsnaps retrieved successfully',
            'data' => $formattedTravsnaps
        ]);
    }

    /**
     * Get a list of all rejected travsnaps
     * 
     * @return \Illuminate\Http\Response
     */
    public function getRejectedTravsnaps()
    {
        \Log::info('Starting getRejectedTravsnaps method');        // Get travsnaps with status 'rejected'
        $rejectedTravsnaps = Travsnap::where('status', 'rejected')
            ->with(['user:id,name,profile_image']) // Get limited user details
            ->select('id', 'title', 'created_at', 'updated_at', 'gallery', 'user_id', 'location', 'description')
            ->orderBy('updated_at', 'desc')
            ->get();

        if ($rejectedTravsnaps->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'No rejected travsnaps found',
                'data' => []
            ]);
        }        // Format the response data
        $formattedTravsnaps = $rejectedTravsnaps->map(function ($travsnap) {
            $galleryImages = [];
            if (is_array($travsnap->gallery) && !empty($travsnap->gallery)) {
                $galleryImages = $travsnap->gallery;
            }
            
            // Get rejection details from moderation record
            $moderation = TravsnapModeration::where('travsnap_id', $travsnap->id)
                ->where('status', 'rejected')
                ->where('is_active', true)
                ->first();
              return [
                'id' => $travsnap->id,
                'title' => $travsnap->title,
                'description' => $travsnap->description,
                'location' => $travsnap->location,
                'submitted_date' => $travsnap->created_at ? $travsnap->created_at->format('Y-m-d H:i:s') : null,
                'rejected_date' => $travsnap->updated_at ? $travsnap->updated_at->format('Y-m-d H:i:s') : null,
                'gallery' => $galleryImages,
                'rejection_reason' => $moderation ? $moderation->rejectionReason : '',
                'user' => [
                    'id' => $travsnap->user->id,
                    'name' => $travsnap->user->name,
                    'profile_image' => $travsnap->user->profile_image ?? null,
                ]
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Rejected travsnaps retrieved successfully',
            'data' => $formattedTravsnaps
        ]);
    }

    /**
     * Get a specific approved travsnap with more details
     * 
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function getApprovedTravsnapDetails($id)
    {
        \Log::info('Starting getApprovedTravsnapDetails method', ['id' => $id]);

        $travsnap = Travsnap::where('id', $id)
            ->where('status', 'approved')
            ->with(['user:id,name,email,profile_image'])
            ->first();

        if (!$travsnap) {
            return response()->json([
                'status' => 'error',
                'message' => 'Approved travsnap not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Approved travsnap details retrieved successfully',
            'data' => $travsnap
        ]);
    }

    /**
     * Get a specific rejected travsnap with more details
     * 
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function getRejectedTravsnapDetails($id)
    {
        \Log::info('Starting getRejectedTravsnapDetails method', ['id' => $id]);

        $travsnap = Travsnap::where('id', $id)
            ->where('status', 'rejected')
            ->with(['user:id,name,email,profile_image'])
            ->first();

        if (!$travsnap) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rejected travsnap not found'
            ], 404);
        }

        // Get rejection details from moderation record
        $moderation = TravsnapModeration::where('travsnap_id', $travsnap->id)
            ->where('status', 'rejected')
            ->where('is_active', true)
            ->first();

        // Add rejection details to the travsnap data
        $travsnapData = $travsnap->toArray();
        $travsnapData['rejection_details'] = [
            'reason' => $moderation ? $moderation->rejectionReason : '',
            'notes' => $moderation ? $moderation->moderator_notes : '',
            'rejected_at' => $moderation && $moderation->rejected_at ? $moderation->rejected_at->format('Y-m-d H:i:s') : null,
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Rejected travsnap details retrieved successfully',
            'data' => $travsnapData
        ]);
    }
}
