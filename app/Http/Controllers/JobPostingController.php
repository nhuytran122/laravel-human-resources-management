<?php

namespace App\Http\Controllers;

use App\Helpers\DateHelper;
use App\Models\JobPosting;
use Illuminate\Http\Request;

class JobPostingController extends Controller
{
    public function index()
    {
        $job_postings = JobPosting::with('position', 'department')
            ->where('status', 'active')
            ->where('deadline', '>=', now())
            ->get();
        return response()->json([
            'data' => $job_postings,
        ], 200);
    }

    public function create()
    {
        
    }

    public function store(Request $request)
    {
        $this->mergeDateFields($request);
        $this->validatePost($request);  
        $job_posting = JobPosting::create($request->all());
        return response()->json([
            'message' => 'Bài đăng tuyển dụng được thêm thành công',
            'data' => $job_posting,
        ], 200);
    }

    public function show(string $id)
    {
        $job_posting = JobPosting::with('position')->findOrFail($id);
        return response()->json([
            'data' => $job_posting
        ], 200);
    }

    public function edit(string $id)
    {
        
    }

    public function update(Request $request, string $id)
    {
        $this->mergeDateFields($request);
        $this->validatePost($request);  
        $job_posting = JobPosting::findOrFail($id);
        $job_posting->update($request->all());
        return response()->json([
            'message' => 'Bài đăng tuyển dụng được cập nhật thành công',
            'data' => $job_posting
        ], 200);

    }
    public function destroy(string $id)
    {
        $job_posting = JobPosting::findOrFail($id);

        $has_application = $job_posting->applications()->exists();

        if ($has_application) {
            return response()->json([
                'message' => 'Không thể xóa vì bài đăng này đã có đơn ứng tuyển'
            ], 400);
        }
        $job_posting->delete();
        return response()->json([
            'message' => 'Bài đăng tuyển dụng đã được xóa thành công'
        ], 200);
    }

    
    private function validatePost(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'position_id' => 'required|exists:positions,id',
            'department_id' => 'required|exists:departments,id',
            'salary_range' => 'nullable|string',
            'deadline' => 'required|date|after:now',
            'status' => 'nullable|in:active,inactive',
        ]);
    }

    private function mergeDateFields(Request $request)
    {
        $request->merge([
            'deadline' => DateHelper::toDateFormat($request->deadline),
        ]);
    }
}