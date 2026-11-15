<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizAttempt extends Model
{
    protected $fillable = [
        'quiz_id',
        'student_id',
        'attempt_number',
        'status',
        'question_order',
        'option_orders',
        'started_at',
        'expires_at',
        'submitted_at',
        'objective_score',
        'essay_score',
        'total_score',
        'percentage',
        'is_passed',
        'needs_manual_grading',
        'graded_by',
        'graded_at',
    ];

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'question_order' => 'array',
            'option_orders' => 'array',
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
            'objective_score' => 'decimal:2',
            'essay_score' => 'decimal:2',
            'total_score' => 'decimal:2',
            'percentage' => 'decimal:2',
            'is_passed' => 'boolean',
            'needs_manual_grading' => 'boolean',
            'graded_at' => 'datetime',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class);
    }
}
