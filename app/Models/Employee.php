<?php

namespace App\Models;

use App\Models\Department;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Employee extends Model implements HasMedia
{
    use LogsActivity, InteractsWithMedia;

    protected $fillable = ['full_name', 'gender', 'date_of_birth', 'phone', 'address', 
    'hire_date','is_working', 'user_id', 'department_id', 'position_id'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['*'])
        ->useLogName('employee')     
        ->logOnlyDirty();
    }
    public function department(){
        return $this->belongsTo(Department::class);
    }
    public function position(){
        return $this->belongsTo(Position::class);
    }
    public function leave_requests(){
        return $this->hasMany(LeaveRequest::class, 'send_by');
    }
    public function leave_balances(){
        return $this->hasMany(LeaveBalance::class);
    }
    public function attendances(){
        return $this->hasMany(Attendance::class);
    }
    public function salaries(){
        return $this->hasMany(Salary::class);
    }
    
    public function approved_requests() {
        return $this->hasMany(LeaveRequest::class, 'approved_by');
    }

    public function manage_department() {
        return $this->hasOne(Department::class, 'manager_id');
    }    

    public function interview_participations()
    {
        return $this->hasMany(InterviewParticipant::class, 'employee_id');
    }

    public function interviews()
    {
        return $this->belongsToMany(Interview::class, 'interview_participants')
                    ->withPivot('feedback', 'score')
                    ->withTimestamps();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $casts = [
        'date_of_birth' => 'date',
        'is_working' => 'boolean',
        'hire_date' => 'date',
    ];
    

}