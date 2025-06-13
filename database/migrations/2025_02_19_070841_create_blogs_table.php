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
    {        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->text('additionalInfo')->nullable();
            $table->longText('content');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('categories');
            $table->json('location')->nullable();
            $table->string('image')->nullable();
            $table->json('gallery')->nullable();
            $table->text('review')->nullable();
            $table->string('operatingHours')->nullable();
            $table->string('entryFee')->nullable();
            $table->json('suitableFor')->nullable();
            $table->string('specialty')->nullable();
            $table->string('closedDates')->nullable();
            $table->string('routeDetails')->nullable();
            $table->string('safetyMeasures')->nullable();
            $table->string('restrictions')->nullable();
            $table->string('climate')->nullable();
            $table->string('travelAdvice')->nullable();
            $table->string('emergencyContacts')->nullable();
            $table->string('assistance')->nullable();
            $table->string('type')->default('General');
            $table->integer('views')->default(0);
            $table->enum('status', ['draft', 'published', 'pending', 'approved', 'rejected'])->default('draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('blogs');
    }
};
 