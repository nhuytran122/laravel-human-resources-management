<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewParticipant extends Model
{
    protected $table = 'interview_participants';
    protected $fillable = [
        'interview_id',
        'employee_id',
        'feedback',
        'score',
    ];

    public function interview()
    {
        return $this->belongsTo(Interview::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}