<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LeaveType extends Model
{
    protected $fillable = ['name', 'description', 'max_days', 'is_paid'];
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['name', 'description' , 'max_days', 'is_paid'])
        ->useLogName('leave_type')     
        ->logOnlyDirty();
    }

    public function leave_requests(){
        return $this->hasMany(LeaveRequest::class);
    }
}