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

            // Modify user_id to string and nullable
            $table->string('user_id')->nullable()->change();

            // Re-add the foreign key constraint (if still needed)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

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
            // Drop the foreign key constraint
            if (Schema::hasTable('blogs') && Schema::hasColumn('blogs', 'user_id')) {
                $table->dropForeign(['user_id']);
            }

            // Revert user_id to its original type (adjust as needed, e.g., unsignedBigInteger)
            $table->unsignedBigInteger('user_id')->nullable()->change();

            // Re-add the foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            // Drop the added columns
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

            // Rename user_id back to author if needed
            if (Schema::hasColumn('blogs', 'user_id')) {
                $table->renameColumn('user_id', 'author');
            }
        });
    }
};