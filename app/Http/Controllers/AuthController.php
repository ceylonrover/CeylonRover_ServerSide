<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // 🔹 User Registration
    public function register(Request $request)
    {
            // \Log::info('Starting register method 1');

        $request->validate([
            'fullName' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $request->fullName,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            // Email not verified by default
            'email_verified_at' => null,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        // Automatically send verification OTP to the user's email
        try {
            // Generate a 6-digit OTP
            $otp = sprintf("%06d", mt_rand(1, 999999));
            
            // Store the OTP in cache for 10 minutes
            \Illuminate\Support\Facades\Cache::put('email_verification_' . $user->email, $otp, now()->addMinutes(10));
            
            // Prepare email data
            $data = [
                'otp' => $otp,
                'email' => $user->email,
            ];
            
            // Send the email
            \Illuminate\Support\Facades\Mail::send('emails.verification-otp', $data, function($message) use ($user) {
                $message->to($user->email)
                        ->subject('Ceylon Rover - Email Verification OTP');
            });
        } catch (\Exception $e) {
            // Log the error but continue with registration
            \Illuminate\Support\Facades\Log::error('Failed to send verification email: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Registration successful. Please check your email for verification OTP.',
            'user' => $user,
            'token' => $token,
        ]);
    }

    // 🔹 User Login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        // Load user details to get profile image path
        $userDetail = $user->detail;
        $profileImagePath = null;
        
        if ($userDetail && $userDetail->profile_image_path) {
            $profileImagePath = $userDetail->profile_image_path;
        } elseif ($user->profile_image) {
            // Fallback to user's profile_image if available
            $profileImagePath = $user->profile_image;
        }

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
            'profile_image_path' => $profileImagePath,
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    // 🔹 Get User Info
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    // 🔹 User Logout
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function updateProfile(Request $request)
    {
        
        $user = $request->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->filled('password')) {
            $user->password = \Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }
      /**
     * Check if the user's email is verified
     */
    public function checkEmailVerification(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'verified' => !is_null($user->email_verified_at),
            'email' => $user->email
        ]);
    }

    /**
     * Verify user's email with OTP
     */
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        $email = $request->email;
        $otp = $request->otp;

        // Get the stored OTP from cache
        $storedOtp = \Illuminate\Support\Facades\Cache::get('email_verification_' . $email);

        if (!$storedOtp) {
            return response()->json([
                'message' => 'OTP has expired. Please request a new one.'
            ], 400);
        }
        
        if ($storedOtp !== $otp) {
            return response()->json([
                'message' => 'Invalid OTP. Please try again.'
            ], 400);
        }

        // Find the user
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found.'
            ], 404);
        }

        // Mark email as verified
        $user->email_verified_at = now();
        $user->save();

        // Clear the OTP from cache
        \Illuminate\Support\Facades\Cache::forget('email_verification_' . $email);
        
        // Create or update user details record
        $userDetail = \App\Models\UserDetail::updateOrCreate(
            ['user_id' => $user->id],
            [
                'user_id' => $user->id,
                'first_name' => explode(' ', $user->name)[0] ?? '',
                'last_name' => count(explode(' ', $user->name)) > 1 ? implode(' ', array_slice(explode(' ', $user->name), 1)) : '',
                'joined_date' => now(),
                'mobile_number' => $user->phone,
                'profile_image_path' => $user->profile_image,
                'blog_count' => 0,
                'travsnap_count' => 0,
                'total_likes' => 0,
                'total_views' => 0
            ]
        );

        return response()->json([
            'message' => 'Email verified successfully.',
            'user' => $user,
            'user_details' => $userDetail
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }
}
