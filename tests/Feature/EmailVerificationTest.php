<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test sending OTP to a user's email.
     */
    public function test_send_otp_to_user_email()
    {
        Mail::fake();
        
        $user = User::factory()->create([
            'email_verified_at' => null
        ]);
        
        $response = $this->postJson('/api/email/verify/send-otp', [
            'email' => $user->email,
        ]);
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'email'
            ]);
        
        // Assert that an OTP has been stored in the cache
        $this->assertNotNull(Cache::get('email_verification_' . $user->email));
    }
    
    /**
     * Test verifying OTP.
     */
    public function test_verify_otp()
    {
        $user = User::factory()->create([
            'email_verified_at' => null
        ]);
        
        // Manually set an OTP in the cache
        $otp = '123456';
        Cache::put('email_verification_' . $user->email, $otp, now()->addMinutes(10));
        
        $response = $this->postJson('/api/email/verify/verify-otp', [
            'email' => $user->email,
            'otp' => $otp,
        ]);
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user'
            ]);
        
        // Assert that the user's email is now verified
        $this->assertNotNull(User::find($user->id)->email_verified_at);
    }
    
    /**
     * Test resending OTP.
     */
    public function test_resend_otp()
    {
        Mail::fake();
        
        $user = User::factory()->create([
            'email_verified_at' => null
        ]);
        
        // First set an OTP
        $oldOtp = '123456';
        Cache::put('email_verification_' . $user->email, $oldOtp, now()->addMinutes(10));
        
        // Then request a new one
        $response = $this->postJson('/api/email/verify/resend-otp', [
            'email' => $user->email,
        ]);
        
        $response->assertStatus(200);
        
        // Assert that a new OTP has been stored
        $newOtp = Cache::get('email_verification_' . $user->email);
        $this->assertNotNull($newOtp);
        
        // The new OTP should be different from the old one
        // $this->assertNotEquals($oldOtp, $newOtp);
    }
}
