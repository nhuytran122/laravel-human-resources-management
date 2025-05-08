<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Interview extends Model
{
    use LogsActivity;
    protected $fillable = ['job_application_id', 'interview_date', 'notes', 'result'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['*'])
        ->useLogName('interview')     
        ->logOnlyDirty();
    }
    public function job_application()
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function participants(){
        return $this->hasMany(InterviewParticipant::class);
    }

    public function interviewers()
    {
        return $this->belongsToMany(Employee::class, 'interview_participants')
                    ->withPivot('feedback', 'score')
                    ->withTimestamps();
    }

    protected $casts = [
        'interview_date' => 'datetime',
    ];
}