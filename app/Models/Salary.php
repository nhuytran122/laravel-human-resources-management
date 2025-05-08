<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Salary extends Model
{
    protected $fillable = ['month', 'year', 'is_paid', 'employee_id'];
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['*'])
        ->useLogName('department')     
        ->logOnlyDirty();
    }
    public function employee(){
        return $this->belongsTo(Employee::class);
    }

    protected $casts = [
        'is_paid' => 'boolean',
        'is_locked' => 'boolean',
    ];
}