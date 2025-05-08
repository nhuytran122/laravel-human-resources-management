<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Department extends Model
{
    protected $fillable = ['name'];
    use LogsActivity;
    public function employees(){
        return $this->hasMany(Employee::class);
    }

    public function manager() {
        return $this->belongsTo(Employee::class, 'manager_id');
    }    

    public function job_postings(){
        return $this->hasMany(JobPosting::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['name'])
        ->useLogName('department')     
        ->logOnlyDirty();
    }
}