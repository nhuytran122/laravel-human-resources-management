<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobOffer extends Model
{

    protected $fillable = [
        'job_application_id',
        'position_id',
        'start_date',
        'offer_deadline',
        'notes',
        'status',
    ];

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function job_application()
    {
        return $this->belongsTo(JobApplication::class);
    }
}