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
    {        Schema::create('travsnap_moderations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('travsnap_id')->constrained('travsnaps')->onDelete('cascade');
            $table->foreignId('moderator_id')->constrained('users');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejectionReason')->nullable();
            $table->text('moderator_notes')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travsnap_moderations');
    }
};
