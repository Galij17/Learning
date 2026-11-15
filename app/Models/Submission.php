<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Submission extends Model
{
    protected $fillable = [
        'assignment_id',
        'student_id',
        'text_answer',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'status',
        'score',
        'feedback',
        'submitted_at',
        'graded_at',
        'graded_by',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'score' => 'decimal:2',
            'submitted_at' => 'datetime',
            'graded_at' => 'datetime',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
