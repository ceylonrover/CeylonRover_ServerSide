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
            // Drop the foreign key constraint if it exists
            if (Schema::hasTable('blogs') && Schema::hasColumn('blogs', 'user_id')) {
                $table->dropForeign(['user_id']); // Drop foreign key constraint
            }

            // Rename author to user_id if needed
            if (Schema::hasColumn('blogs', 'author')) {
                $table->renameColumn('author', 'user_id');
            }

            // Modify user_id to unsignedBigInteger and nullable (to match users.id type)
            $table->unsignedBigInteger('user_id')->nullable()->change();

            // Re-add the foreign key constraint (if still needed)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            // Only add columns that don't exist in the original blogs table
            // Most fields already exist in the original migration, so we'll only add truly new ones
            
            // Convert existing snake_case columns to match if needed
            if (Schema::hasColumn('blogs', 'operatingHours') && !Schema::hasColumn('blogs', 'operating_hours')) {
                $table->renameColumn('operatingHours', 'operating_hours');
            }
            if (Schema::hasColumn('blogs', 'entryFee') && !Schema::hasColumn('blogs', 'entry_fee')) {
                $table->renameColumn('entryFee', 'entry_fee');
            }
            if (Schema::hasColumn('blogs', 'suitableFor') && !Schema::hasColumn('blogs', 'suitable_for')) {
                $table->renameColumn('suitableFor', 'suitable_for');
            }
            if (Schema::hasColumn('blogs', 'closedDates') && !Schema::hasColumn('blogs', 'closed_dates')) {
                $table->renameColumn('closedDates', 'closed_dates');
            }
            if (Schema::hasColumn('blogs', 'routeDetails') && !Schema::hasColumn('blogs', 'route_details')) {
                $table->renameColumn('routeDetails', 'route_details');
            }
            if (Schema::hasColumn('blogs', 'safetyMeasures') && !Schema::hasColumn('blogs', 'safety_measures')) {
                $table->renameColumn('safetyMeasures', 'safety_measures');
            }
            if (Schema::hasColumn('blogs', 'travelAdvice') && !Schema::hasColumn('blogs', 'travel_advice')) {
                $table->renameColumn('travelAdvice', 'travel_advice');
            }
            if (Schema::hasColumn('blogs', 'emergencyContacts') && !Schema::hasColumn('blogs', 'emergency_contacts')) {
                $table->renameColumn('emergencyContacts', 'emergency_contacts');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            // Drop the foreign key constraint
            if (Schema::hasTable('blogs') && Schema::hasColumn('blogs', 'user_id')) {
                $table->dropForeign(['user_id']);
            }

            // Revert user_id to its original type (unsignedBigInteger)
            $table->unsignedBigInteger('user_id')->nullable()->change();

            // Re-add the foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            // Revert column names back to camelCase if they were renamed
            if (Schema::hasColumn('blogs', 'operating_hours') && !Schema::hasColumn('blogs', 'operatingHours')) {
                $table->renameColumn('operating_hours', 'operatingHours');
            }
            if (Schema::hasColumn('blogs', 'entry_fee') && !Schema::hasColumn('blogs', 'entryFee')) {
                $table->renameColumn('entry_fee', 'entryFee');
            }
            if (Schema::hasColumn('blogs', 'suitable_for') && !Schema::hasColumn('blogs', 'suitableFor')) {
                $table->renameColumn('suitable_for', 'suitableFor');
            }
            if (Schema::hasColumn('blogs', 'closed_dates') && !Schema::hasColumn('blogs', 'closedDates')) {
                $table->renameColumn('closed_dates', 'closedDates');
            }
            if (Schema::hasColumn('blogs', 'route_details') && !Schema::hasColumn('blogs', 'routeDetails')) {
                $table->renameColumn('route_details', 'routeDetails');
            }
            if (Schema::hasColumn('blogs', 'safety_measures') && !Schema::hasColumn('blogs', 'safetyMeasures')) {
                $table->renameColumn('safety_measures', 'safetyMeasures');
            }
            if (Schema::hasColumn('blogs', 'travel_advice') && !Schema::hasColumn('blogs', 'travelAdvice')) {
                $table->renameColumn('travel_advice', 'travelAdvice');
            }
            if (Schema::hasColumn('blogs', 'emergency_contacts') && !Schema::hasColumn('blogs', 'emergencyContacts')) {
                $table->renameColumn('emergency_contacts', 'emergencyContacts');
            }

            // Rename user_id back to author if needed
            if (Schema::hasColumn('blogs', 'user_id')) {
                $table->renameColumn('user_id', 'author');
            }
        });
    }
};