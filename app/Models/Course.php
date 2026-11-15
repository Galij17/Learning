<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'subject_id',
        'academic_period_id',
        'instructor_id',
        'title',
        'slug',
        'code',
        'description',
        'thumbnail_path',
        'status',
        'assignment_weight',
        'quiz_weight',
        'exam_weight',
        'min_completion_percentage',
        'min_passing_score',
        'certificate_enabled',
    ];

    protected function casts(): array
    {
        return [
            'assignment_weight' => 'integer',
            'quiz_weight' => 'integer',
            'exam_weight' => 'integer',
            'min_completion_percentage' => 'integer',
            'min_passing_score' => 'decimal:2',
            'certificate_enabled' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'enrollments', 'course_id', 'student_id')
            ->withPivot(['status', 'progress_percentage', 'final_score', 'enrolled_at', 'completed_at'])
            ->withTimestamps();
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('meeting_number')->orderBy('sort_order');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class)->orderBy('sort_order');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class)->orderBy('due_at');
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class)->orderByDesc('created_at');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class)->orderByDesc('is_pinned')->orderByDesc('published_at');
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(Discussion::class)->orderByDesc('is_pinned')->orderByDesc('created_at');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }
}
