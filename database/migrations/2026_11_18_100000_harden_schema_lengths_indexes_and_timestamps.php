<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->text('video_url')->nullable()->change();
            $table->index(['course_id', 'is_published']);
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->text('description')->change();
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable()->change();
            $table->timestamp('expires_at')->nullable()->change();
        });

        Schema::table('material_progress', function (Blueprint $table) {
            $table->index('student_id');
        });

        Schema::table('grades', function (Blueprint $table) {
            $table->index('student_id');
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->index('student_id');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['created_by']);
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->dropIndex(['student_id']);
        });

        Schema::table('grades', function (Blueprint $table) {
            $table->dropIndex(['student_id']);
        });

        Schema::table('material_progress', function (Blueprint $table) {
            $table->dropIndex(['student_id']);
        });

        Schema::table('materials', function (Blueprint $table) {
            $table->dropIndex(['course_id', 'is_published']);
            $table->string('video_url')->nullable()->change();
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('description')->change();
        });
    }
};
