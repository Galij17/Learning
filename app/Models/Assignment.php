<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'course_id',
        'lesson_id',
        'created_by',
        'title',
        'description',
        'attachment_path',
        'attachment_name',
        'due_at',
        'max_score',
        'submission_type',
        'allow_late_submission',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'max_score' => 'decimal:2',
            'allow_late_submission' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }
}
