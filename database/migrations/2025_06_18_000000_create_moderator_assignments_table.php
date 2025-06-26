<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('moderator_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('moderator_id')->constrained('users');
            $table->unsignedBigInteger('content_id');
            $table->string('content_type'); // 'blog' or 'travsnap'
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Add index for content polymorphic relationship
            $table->index(['content_type', 'content_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('moderator_assignments');
    }
};
