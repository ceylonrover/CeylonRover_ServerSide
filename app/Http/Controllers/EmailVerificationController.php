<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class EmailVerificationController extends Controller
{
    /**
     * Send OTP to the user's email for verification
     */
    public function sendOTP(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $email = $request->email;
        
        // Generate a 6-digit OTP
        $otp = sprintf("%06d", mt_rand(1, 999999));
        
        // Store the OTP in cache for 10 minutes
        // Using email as the key for the OTP
        Cache::put('email_verification_' . $email, $otp, now()->addMinutes(10));
        
        // Prepare email data
        $data = [
            'otp' => $otp,
            'email' => $email,
        ];
        
        // Send the email
        try {
            Mail::send('emails.verification-otp', $data, function($message) use ($email) {
                $message->to($email)
                        ->subject('Ceylon Rover - Email Verification OTP');
            });
            
            return response()->json([
                'message' => 'OTP sent successfully to your email',
                'email' => $email
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send OTP email',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Verify the OTP provided by the user
     */
    public function verifyOTP(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:6',
        ]);
        
        $email = $request->email;
        $userOtp = $request->otp;
        
        // Get the stored OTP from cache
        $storedOtp = Cache::get('email_verification_' . $email);
        
        if (!$storedOtp) {
            return response()->json([
                'message' => 'OTP has expired. Please request a new one.'
            ], 400);
        }
        
        if ($userOtp != $storedOtp) {
            return response()->json([
                'message' => 'Invalid OTP. Please try again.'
            ], 400);
        }
        
        // If OTP is valid, mark the user's email as verified
        $user = User::where('email', $email)->first();
        $user->email_verified_at = now();
        $user->save();
        
        // Create or update user details record
        $userDetail = \App\Models\UserDetail::updateOrCreate(
            ['user_id' => $user->id],
            [
                'user_id' => $user->id,
                'first_name' => explode(' ', $user->name)[0] ?? '',
                'last_name' => count(explode(' ', $user->name)) > 1 ? implode(' ', array_slice(explode(' ', $user->name), 1)) : '',
                'joined_date' => now(),
                'mobile_number' => $user->phone,
                'blog_count' => 0,
                'travsnap_count' => 0,
                'total_likes' => 0,
                'total_views' => 0
            ]
        );
        
        // Remove the OTP from cache
        Cache::forget('email_verification_' . $email);
        
        return response()->json([
            'message' => 'Email verified successfully',
            'user' => $user,
            'user_details' => $userDetail
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * Resend OTP if the previous one expired
     */
    public function resendOTP(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);
        
        $email = $request->email;
        
        // Delete any existing OTP for this email
        Cache::forget('email_verification_' . $email);
        
        // Call the sendOTP method to generate and send a new OTP
        return $this->sendOTP($request);
    }
}
