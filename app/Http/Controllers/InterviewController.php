<?php

namespace App\Http\Controllers;

use App\Helpers\DateHelper;
use App\Mail\InterviewFailedMail;
use App\Mail\InterviewInvitation;
use App\Mail\InterviewInvitationMail;
use App\Models\Interview;
use App\Models\InterviewParticipant;
use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class InterviewController extends Controller
{
    public function index()
    {
        $interviews = Interview::where('interview_date', '>=', now())
        ->with('job_application') 
        ->get();
        return response()->json([
            'data' => $interviews,
        ], 200);
    }

    
    public function create($job_application_id)
    {
        $application = JobApplication::findOrFail($job_application_id);

        return response()->json([
            'message' => 'Lấy tạm thông tin đơn ứng tuyển để tạo interview thành công',
            'job_application' => $application,
        ], 200);
    }

    public function store(Request $request)
    {
        $this->mergeDateFields($request);
        $this->validateInterview($request);  
        return DB::transaction(function () use ($request) {
            $application = JobApplication::findOrFail($request->job_application_id);
            // Chỉ đơn ứng tuyển đã được phê duyệt mới được tạo lịch phỏng vấn
            if($application->status !== 'accepted'){
                return response()->json([
                    'message' => 'Đơn ứng tuyển này đang là '. $application->status . ', không thể tạo lịch phỏng vấn'
                ], 422);
            }
            $interview = Interview::create($request->all());
            $application->update([
                'status' => 'pending_interview'
            ]);

            if ($request->has('interviewer_ids')) {
                $interview->interviewers()->sync($request->interviewer_ids);
            }
            // if ($request->has('interviewer_ids')) {
            //     foreach ($request->interviewer_ids as $employeeId) {
            //         InterviewParticipant::create([
            //             'interview_id' => $interview->id,
            //             'employee_id' => $employeeId,
            //         ]);
            //     }
            // }

            // Mail::to($application->email)->queue(new InterviewInvitationMail($interview->load('job_application', 'interviewers')));
            Mail::to($application->email)->send(new InterviewInvitationMail($interview->load('job_application', 'interviewers')));
            return response()->json([
                'message' => 'Đã lên lịch phỏng vấn thành công',
                'data' => $interview->load('job_application', 'interviewers'),
            ], 200);
        });
    }

    public function show(string $id)
    {
        $interview = Interview::with('job_application', 'interviewers')->findOrFail($id);
        return response()->json([
            'data' => $interview
        ], 200);
    }

    public function edit(string $id)
    {
        
    }

    public function update(Request $request, string $id)
    {
        $this->mergeDateFields($request);
        $this->validateInterview($request);  
        $interview = Interview::findOrFail($id);

        if($interview->result != 'pending'){
            return response()->json([
                'message' => 'Lịch phỏng vấn này đã ' + $interview->result + ', không thể sửa đổi'
            ], 422);
        }
        return DB::transaction(function () use ($request, $interview) {
            if ($request->has('interviewer_ids')) {
                $interview->interviewers()->sync($request->interviewer_ids);
            }
            $interview->update([
                'interview_date' => $request->interview_date,
                'notes' => $request->notes
            ]);
            // Or:
            // if ($request->has('interviewer_ids')) {
            //     InterviewParticipant::where('interview_id', $interview->id)->delete();
            //     foreach ($request->interviewer_ids as $employeeId) {
            //         InterviewParticipant::create([
            //             'interview_id' => $interview->id,
            //             'employee_id' => $employeeId,
            //         ]);
            //     }
            // }
            Mail::to($interview->job_application->email)->send(new InterviewInvitationMail($interview->load('job_application', 'interviewers'), true));
            
            return response()->json([
                'message' => 'Đã cập nhật phỏng vấn thành công',
                'data' => $interview->load('job_application', 'interviewers'),
            ], 200);
        });
    }
    public function destroy(string $id)
    {
        $interview = Interview::findOrFail($id);
        $interview->delete();
        return response()->json([
            'message' => 'Xóa lịch phỏng vấn thành công'
        ], 200);
    }

    public function updateResult(Request $request, $id){
        $interview = Interview::findOrFail($id);
        $request->validate([
            'result' => 'required|in:pass,fail',
            'notes' => 'nullable|string',
        ]);        

        if($interview->result != 'pending'){
            return response()->json([
                'message' => 'Lịch phỏng vấn này đã ' + $interview->result + ', không thể cập nhật kết quả'
            ], 422);
        }
        return DB::transaction(function () use ($request, $interview) {
            $request_result = $request->result;
            $application_status = match ($request_result) {
                'pass' => 'pending_offer',
                'fail' => 'rejected',
            };
            $interview->update([
                'result' => $request_result,
                'notes' => $request->notes,
            ]);

            $interview_result = $interview->result;

            $job_application = $interview->job_application;
            $job_application->update(['status' => $application_status]);

            if($interview_result == 'pass'){
                return response()->json([
                    'message' => 'Ứng viên đã pass phỏng vấn. Vui lòng tạo offer',
                    'data' => $interview->load('job_application'),
                    'next_url' => route('job-offers.create', $job_application->id)
                ], 201) -> header('Location', route('job-offers.create', $job_application->id));
            }
            else if($interview_result == 'fail'){
                $interview = Interview::with('job_application.job_posting.position', 'interviewers')->findOrFail($interview->id);
                Mail::to($interview->job_application->email)->send(new InterviewFailedMail($interview));
            }
            return response()->json([
                'message' => 'Kết quả buổi phỏng vấn đã được cập nhật',
                'data' => $interview->load('job_application'),
            ], 200);
        });
    }

    public function updateFeedback(Request $request, $id)
    {
        $request->validate([
            'score' => 'required|numeric|min:0|max:100',
        ]);

        $employee_id = Auth::user()->employee->id;

        $interview_participant = InterviewParticipant::where('interview_id', $id)
            ->where('employee_id', $employee_id)
            ->firstOrFail();

        if ($interview_participant->interview->result !== 'pending') {
            return response()->json([
                'message' => 'Buổi phỏng vấn này đã có kết quả, đã quá thời gian đánh giá.',
            ], 422);
        }

        $interview_participant->update([
            'score' => $request->score,
            'feedback' => $request->feedback,
        ]);

        return response()->json([
            'message' => 'Đánh giá buổi phỏng vấn đã được cập nhật',
            'data' => $interview_participant->load('interview'),
        ], 200);
    }

    private function mergeDateFields(Request $request)
    {
        $request->merge([
            'interview_date' => DateHelper::toDateFormat($request->interview_date),
        ]);
    }
    
    private function validateInterview(Request $request)
    {
        $request->validate([
            'job_application_id' => 'required|exists:job_applications,id',
            'interviewer_ids' => 'required|array', 
            'interviewer_ids.*' => 'exists:employees,id',
            'interview_date' => 'required|date|after:now',
            'notes' => 'nullable|string',
            'result' => 'nullable|in:pass,fail,pending'
        ]);
    }
}