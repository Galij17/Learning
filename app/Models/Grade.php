<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grade extends Model
{
    protected $fillable = [
        'course_id',
        'student_id',
        'assignment_avg',
        'quiz_avg',
        'exam_avg',
        'final_score',
        'letter_grade',
        'instructor_feedback',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'assignment_avg' => 'decimal:2',
            'quiz_avg' => 'decimal:2',
            'exam_avg' => 'decimal:2',
            'final_score' => 'decimal:2',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
