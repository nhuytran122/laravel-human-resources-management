<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = ['employee_id', 'month', 'year', 'working_days', 'leave_days', 'paid_leave_days'];
    public function employee(){
        return $this->belongsTo(Employee::class);
    }

}