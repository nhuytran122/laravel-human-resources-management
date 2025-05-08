<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $fillable = ['start_date', 'end_date', 'reason', 'status', 'employee_id', 'leave_type_id', 'approved_by'];
    public function send_by(){
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function approved_by() {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    public function leave_type(){
        return $this->belongsTo(LeaveType::class);
    }

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];
}