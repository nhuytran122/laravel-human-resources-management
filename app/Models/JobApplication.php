<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class JobApplication extends Model implements HasMedia
{
    use InteractsWithMedia;
    protected $fillable = [
        'job_posting_id',
        'full_name',
        'date_of_birth',
        'gender',
        'address',
        'email',
        'phone',
        'notes',
        'status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function job_posting()
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function interview()
    {
        return $this->hasOne(Interview::class);
    }

    public function job_offer()
    {
        return $this->hasOne(JobOffer::class);
    }
}