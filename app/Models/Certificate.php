<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    protected $fillable = [
        'course_id',
        'student_id',
        'certificate_number',
        'final_score',
        'verification_hash',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'final_score' => 'decimal:2',
            'issued_at' => 'datetime',
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
}
