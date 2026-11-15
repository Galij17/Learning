<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->unsignedInteger('meeting_number')->default(1);
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_published')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['course_id', 'sort_order']);
        });

        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title', 180);
            $table->enum('type', ['pdf', 'ppt', 'docx', 'image', 'video', 'link', 'text'])->index();
            $table->string('storage_disk', 30)->default('local');
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('video_url')->nullable();
            $table->longText('content')->nullable();
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_published')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['lesson_id', 'sort_order']);
        });

        Schema::create('material_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_completed')->default(true);
            $table->timestamp('completed_at')->useCurrent();
            $table->timestamps();

            $table->unique(['material_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_progress');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('lessons');
    }
};
