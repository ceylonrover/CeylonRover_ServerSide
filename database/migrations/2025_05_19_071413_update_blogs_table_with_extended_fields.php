<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            // Rename author to user_id if needed
            if (Schema::hasColumn('blogs', 'author')) {
                $table->renameColumn('author', 'user_id');
            }

            // Ensure user_id is a string (if you're using UUIDs) or change to unsignedBigInteger for integer IDs
            $table->string('user_id')->nullable()->change();

            // Add new fields
            $table->text('operating_hours')->nullable();
            $table->string('entry_fee')->nullable();
            $table->json('suitable_for')->nullable();
            $table->string('specialty')->nullable();
            $table->string('closed_dates')->nullable();
            $table->text('route_details')->nullable();
            $table->text('safety_measures')->nullable();
            $table->text('restrictions')->nullable();
            $table->string('climate')->nullable();
            $table->text('travel_advice')->nullable();
            $table->string('emergency_contacts')->nullable();
            $table->text('assistance')->nullable();
            $table->string('type')->default('General');
            $table->unsignedBigInteger('views')->default(0);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        
        Schema::table('blogs', function (Blueprint $table) {
            // Optional: revert changes if needed
            $table->dropColumn([
                'operating_hours',
                'entry_fee',
                'suitable_for',
                'specialty',
                'closed_dates',
                'route_details',
                'safety_measures',
                'restrictions',
                'climate',
                'travel_advice',
                'emergency_contacts',
                'assistance',
                'type',
                'views'
            ]);
        });
    }
};
