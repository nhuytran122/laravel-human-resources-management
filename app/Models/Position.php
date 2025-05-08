<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Position extends Model
{
    protected $fillable = ['name'];
    use LogsActivity;
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['name'])
        ->useLogName('position')     
        ->logOnlyDirty();
    }
    public function employees(){
        return $this->hasMany(Employee::class);
    }

    public function salary_config()
    {
        return $this->hasOne(SalaryConfig::class);
    }

    public function job_postings(){
        return $this->hasMany(JobPosting::class);
    }

    public function job_offers(){
        return $this->hasMany(JobOffer::class);
    }
}