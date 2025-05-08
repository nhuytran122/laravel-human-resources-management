<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    protected $fillable = ['remaining_days', 'year', 'employee_id', 'leave_type_id'];
    public function employee(){
        return $this->belongsTo(Employee::class);
    }
}