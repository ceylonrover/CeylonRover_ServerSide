<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, ensure the role column exists in users table
        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('user')->after('is_active');
            });
        }

        // Create super admin user
        DB::table('users')->insert([
            'name' => 'Super Admin',
            'email' => 'super@admin.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
            'role' => 'superAdmin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create first admin user
        DB::table('users')->insert([
            'name' => 'Admin One',
            'email' => 'admin1@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
            'role' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create second admin user
        DB::table('users')->insert([
            'name' => 'Admin Two',
            'email' => 'admin2@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
            'role' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the admin users
        DB::table('users')->whereIn('email', [
            'super@admin.com',
            'admin1@example.com',
            'admin2@example.com',
        ])->delete();
    }
};
