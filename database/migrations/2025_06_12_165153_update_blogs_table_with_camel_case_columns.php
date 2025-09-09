<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('blogs', function (Blueprint $table) {
            // Drop the columns with snake_case naming if they exist
            if (Schema::hasColumn('blogs', 'suitable_for')) {
                $table->dropColumn('suitable_for');
            }
            if (Schema::hasColumn('blogs', 'operating_hours')) {
                $table->dropColumn('operating_hours');
            }
            if (Schema::hasColumn('blogs', 'entry_fee')) {
                $table->dropColumn('entry_fee');
            }
            if (Schema::hasColumn('blogs', 'closed_dates')) {
                $table->dropColumn('closed_dates');
            }
            if (Schema::hasColumn('blogs', 'route_details')) {
                $table->dropColumn('route_details');
            }
            if (Schema::hasColumn('blogs', 'safety_measures')) {
                $table->dropColumn('safety_measures');
            }
            if (Schema::hasColumn('blogs', 'travel_advice')) {
                $table->dropColumn('travel_advice');
            }
            if (Schema::hasColumn('blogs', 'emergency_contacts')) {
                $table->dropColumn('emergency_contacts');
            }
            if (Schema::hasColumn('blogs', 'author')) {
                $table->dropColumn('author');
            }
            if (Schema::hasColumn('blogs', 'is_approved')) {
                $table->dropColumn('is_approved');
            }
            
            // Add the camelCase columns if they don't exist
            if (!Schema::hasColumn('blogs', 'suitableFor')) {
                $table->json('suitableFor')->nullable();
            }
            if (!Schema::hasColumn('blogs', 'operatingHours')) {
                $table->string('operatingHours')->nullable();
            }
            if (!Schema::hasColumn('blogs', 'entryFee')) {
                $table->string('entryFee')->nullable();
            }
            if (!Schema::hasColumn('blogs', 'closedDates')) {
                $table->string('closedDates')->nullable();
            }
            if (!Schema::hasColumn('blogs', 'routeDetails')) {
                $table->text('routeDetails')->nullable();
            }
            if (!Schema::hasColumn('blogs', 'safetyMeasures')) {
                $table->text('safetyMeasures')->nullable();
            }
            if (!Schema::hasColumn('blogs', 'travelAdvice')) {
                $table->text('travelAdvice')->nullable();
            }
            if (!Schema::hasColumn('blogs', 'emergencyContacts')) {
                $table->text('emergencyContacts')->nullable();
            }
            
            // Make sure user_id column exists
            if (!Schema::hasColumn('blogs', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            }
            
            // Make additionalinfo and review nullable if they aren't already
            if (Schema::hasColumn('blogs', 'additionalinfo')) {
                $table->text('additionalinfo')->nullable()->change();
            }
            if (Schema::hasColumn('blogs', 'review')) {
                $table->text('review')->nullable()->change();
            }
            
            // Update the status enum to include all possible states
            if (Schema::hasColumn('blogs', 'status')) {
                $table->enum('status', ['draft', 'published', 'pending', 'approved', 'rejected'])->default('draft')->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No need to reverse these changes as they are to fix inconsistencies
    }
};
