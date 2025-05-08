<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryConfig extends Model
{
    protected $fillable = ['base_salary', 'allowance', 'position_id'];
    public function position()
    {
        return $this->belongsTo(Position::class);
    }
}