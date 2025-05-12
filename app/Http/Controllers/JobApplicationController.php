<?php

namespace App\Http\Controllers;

use App\Helpers\DateHelper;
use App\Mail\JobApplicationRejected;
use App\Mail\JobApplicationRejectedMail;
use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class JobApplicationController extends Controller
{
    public function index()
    {
        $job_applications = JobApplication::with('job_posting')->get();
        return response()->json([
            'data' => $job_applications,
        ], 200);
    }

    public function create()
    {
        
    }

    public function store(Request $request)
    {
        $this->mergeDateFields($request);
        $this->validateApplication($request);  
        $job_application = JobApplication::create($request->all());
        $job_application->addMediaFromRequest('cv')->toMediaCollection('cvs');
        return response()->json([
            'message' => 'Đơn ứng tuyển đã được thêm thành công',
            'data' => $job_application,
        ], 200);
    }

    public function show(string $id)
    {
        $job_application = JobApplication::with('job_posting')->findOrFail($id);
    
        return response()->json([
            'data' => $job_application
        ], 200);
    }
    

    public function edit(string $id)
    {
        
    }

    public function update(Request $request, string $id)
    {
        $this->mergeDateFields($request);
        $this->validateApplication($request);  
        $job_application = JobApplication::findOrFail($id);
        $job_application->update($request->all());
        return response()->json([
            'message' => 'Đơn ứng tuyển đã được cập nhật thành công',
            'data' => $job_application
        ], 200);

    }
    public function destroy(string $id)
    {
        $job_application = JobApplication::findOrFail($id);

        $has_application = $job_application->interview()->exists();

        if ($has_application) {
            return response()->json([
                'message' => 'Không thể xóa vì đơn ứng tuyển này đã được sắp xếp phỏng vấn'
            ], 422);
        }
        $job_application->delete();
        return response()->json([
            'message' => 'Xóa đơn ứng tuyển thành công'
        ], 200);
    }

    public function handleJobApplication(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|in:screening,rejected,accepted',
        ]);
        return DB::transaction(function () use ($request, $id) {
            $job_application = JobApplication::findOrFail($id);
            $job_application_status = $job_application->status;

            if ($job_application_status !== 'applied' && $job_application_status !== 'screening') {
                return response()->json([
                    'message' => 'Đơn ứng tuyển này đã được ' . $job_application->status . '.',
                ], 422);
            }
            $job_application->update([
                'status' => $request->status
            ]);

            if ($job_application->status == 'rejected') {
                Mail::to($job_application->email)->send(new JobApplicationRejectedMail($job_application));
            }
            else if($job_application->status == 'accepted'){
                return response()->json([
                    'message' => 'Phê duyệt đơn ứng tuyển thành công',
                    'data' => $job_application,
                    'next_url' => route('interviews.create', $job_application->id),
                ], 201)->header('Location', route('interviews.create', $job_application->id));  
            }
            return response()->json([
                'message' => 'Đơn ứng tuyển đã được cập nhật trạng thái ' . $request->status . ' thành công.',
                'data' => $job_application,
            ], 200);
        });
    }

    
    private function validateApplication(Request $request)
    {
        $request->validate([
            'job_posting_id' => 'required|exists:job_postings,id',
            'full_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'email' => 'required|email|max:255',
            'gender'        => 'required|string|in:female,male,other',
            'phone' => [
                'required',
                'regex:/(?:\+84|0084|0)[235789][0-9]{1,2}[0-9]{7}(?:[^\d]+|$)/'
            ],
            'address'       => 'required|string|max:255',
            'cv' => 'required|file|mimes:pdf,doc,docx|max:5120',
        ]);
    }

    private function mergeDateFields(Request $request)
    {
        $request->merge([
            'date_of_birth' => DateHelper::toDateFormat($request->date_of_birth),
        ]);
    }
}