<?php

namespace App\Http\Controllers;

use App\Helpers\DateHelper;
use App\Mail\JobOfferMail;
use App\Models\JobApplication;
use App\Models\JobOffer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class JobOfferController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => JobOffer::with(['position', 'job_application'])->get()
        ]);
        
    }

    public function create(string $job_application_id)
    {
        $job_application = JobApplication::findOrFail($job_application_id);
        return response()->json([
            'message' => 'Thông tin job application để tạo offer:',
            'job application' => $job_application,
        ]);
    }

    public function store(Request $request)
    {
        $this->mergeDateFields($request);
        $this->validateJobOffer($request);
        return DB::transaction(function () use ($request) {
            $exists_offer = JobOffer::where('job_application_id', $request->job_application_id)->first();
            if ($exists_offer) {
                return response()->json([
                    'message' => 'Offer cho đơn ứng tuyển này đã tồn tại',
                    'data' => $exists_offer,
                ], 422);
            }
            $job_offer = JobOffer::create($request->all());
            $job_application = $job_offer->job_application;
            
            $job_application->update([
                'status' => 'offered'
            ]);
            Mail::to($job_application->email)->send(new JobOfferMail($job_offer));
            return response()->json([
                'message' => 'Job Offer đã được thêm thành công',
                'data' => $job_offer->load('position')
            ]);
        });
    }

    public function show(string $id)
    {
        $job_offer = JobOffer::findOrFail($id);
        return response()->json([
            'data' => $job_offer
        ]);
    }

    public function edit(JobOffer $jobOffer)
    {
        //
    }
    //TODO
    public function update(Request $request, string $id)
    {
        $job_offer = JobOffer::findOrFail($id);
        $offer_status = $job_offer->status;
        if($offer_status != 'pending'){
            return response()->json([
                'message' => 'Job offer này đã ' .$offer_status. ', không thể sửa đổi'
            ], 422);
        }
        
    }

    public function destroy(string $id)
    {
        $job_offer = JobOffer::findOrFail($id);
        $job_offer->delete();
        return response()->json([
            'message' => 'Xóa job offer thành công'
        ]);
    }

    public function updateStatus(Request $request, string $id){
        $request->validate([
            'status' => 'required|in:accepted,rejected',
        ]);
        return DB::transaction(function () use ($request, $id) {
            $job_offer = JobOffer::findOrFail($id);
            $job_offer_status = $job_offer->status;
            $job_application = $job_offer->job_application;

            $request_status = $request->status;

            if($job_offer_status !== 'pending'){
                return response()->json([
                    'message' => 'Job Offer này đã '. $job_offer_status .', không thể cập nhật'
                ]);
            }
            $job_offer->update([
                'status' => $request_status
            ]);           

            if($request_status == 'accepted'){
                return response()->json([
                    'message' => 'Job offer đã được chấp nhận. Tiến hành tạo nhân viên.',
                    'data' => [
                        'suggested_employee_data' => [
                            'full_name' => $job_application->full_name,
                            'gender' => $job_application->gender,
                            'date_of_birth' => $job_application->date_of_birth,
                            'phone' => $job_application->phone,
                            'address' => $job_application->address,
                            'position_id' => $job_offer->position_id,
                            'email' => $job_application->email, 
                            'next_url' => route('employees.create-from-job-app', $job_application->id)
                        ]
                    ]
                ], 201)->header('Location', route('employees.create-from-job-app', $job_application->id));
            }
            else{
                $job_application->update([
                    'status' => 'declined'
                ]);
                return response()->json([
                    'message' => 'Đã cập nhật trạng thái của job offer',
                    'data' => $job_offer
                ]);
            }
        });
    }
    private function mergeDateFields(Request $request)
    {
        $request->merge([
            'start_date' => DateHelper::toDateFormat($request->start_date),
            'offer_deadline' => DateHelper::toDateFormat($request->offer_deadline)
        ]);
    }
    
    private function validateJobOffer(Request $request)
    {
        $request->validate([
            'job_application_id' => 'required|exists:job_applications,id',
            'position_id' => 'required|exists:positions,id', 
            'start_date' => 'required|date|after:now',
            'offer_deadline' => 'required|date|after:now|before:start_date',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:pending,accepted,rejected'
        ]);
    }
}