<?php

namespace App\Http\Controllers;

use App\Exports\LeaveRequestExport;
use App\Helpers\DateHelper;
use App\Mail\LeaveRequestApprovedMail;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class LeaveRequestController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view-leave-request', only: ['index', 'show']),
            new Middleware('permission:create-leave-request', only: ['store']),
            new Middleware('permission:edit-leave-request', only: ['update']),
            new Middleware('permission:delete-leave-request', only: ['destroy']),
            new Middleware('permission:handle-leave-request', only: ['approveOrReject']),
            new Middleware('permission:mark-absence', only: ['markAbsence']),
        ];
    }

    public function index()
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'message' => 'Không tìm thấy thông tin nhân viên của bạn.'
            ], 403);
        }

        if ($user->hasRole('manager')) {
            $department = Department::where('manager_id', $employee->id)->first();

            if (!$department) {
                return response()->json([
                    'message' => 'Bạn không phải là quản lý của bất kỳ phòng ban nào.'
                ], 403);
            }
            $employeeIds = $department->employees()->pluck('id');
            $leaveRequests = LeaveRequest::whereIn('employee_id', $employeeIds)->get();
        } else {
            if ($user->hasRole('admin') || $user->hasRole('hr')) {
                $leaveRequests = LeaveRequest::all();
            } else {
                $leaveRequests = LeaveRequest::where('employee_id', $user->employee->id)->get();
            }
        }
        return response()->json($leaveRequests, 200);
    }

    public function show(string $id)
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'message' => 'Không tìm thấy thông tin nhân viên của bạn.'
            ], 403);
        }
        $leaveRequest = LeaveRequest::findOrFail($id);

        if ($user->hasRole('admin') || $user->hasRole('hr')) {
            return response()->json($leaveRequest);
        }

        if ($user->hasRole('manager')) {
            $department = Department::where('manager_id', $employee->id)->first();

            if (!$department) {
                return response()->json([
                    'message' => 'Bạn không phải là quản lý của bất kỳ phòng ban nào.'
                ], 403);
            }

            if ($leaveRequest->employee->department_id !== $department->id) {
                return response()->json([
                    'message' => 'Bạn không có quyền xem yêu cầu nghỉ phép này.'
                ], 403);
            }
            return response()->json($leaveRequest);
        }

        if ($leaveRequest->employee_id !== $employee->id) {
            return response()->json([
                'message' => 'Bạn không có quyền xem yêu cầu nghỉ phép này.'
            ], 403);
        }

        return response()->json($leaveRequest);
    }

    public function create()
    {
    }

    public function store(Request $request)
    {
        $this->mergeDateFields($request);
        $this->validateLeaveRequest($request);

        return DB::transaction(function () use ($request) {
            $user = Auth::user();

            $result = $this->checkLeaveConstraints($user->employee->id, $request->leave_type_id, $request->start_date, $request->end_date);
            if (!$result['status']) {
                return response()->json(['message' => $result['message']], 400);
            }
            $newLeaveRequest = LeaveRequest::create([
                'start_date'    => $request->start_date,
                'end_date'      => $request->end_date,
                'reason'        => $request->reason,
                'leave_type_id' => $request->leave_type_id,
                'employee_id'   => $user->employee->id,
            ]);

            return response()->json([
                'message'  => 'Yêu cầu nghỉ phép được thêm thành công',
                'leave_request' => $newLeaveRequest
            ], 201);
        });
    }

    public function edit(string $id)
    {
    }

    public function update(Request $request, string $id)
    {
        $this->mergeDateFields($request);
        $this->validateLeaveRequest($request);

        $leave_request = LeaveRequest::findOrFail($id);

        if($leave_request->employee->id !== Auth::user()->employee->id){
            return response()->json([
                'message' => 'Bạn không có quyền chỉnh sửa yêu cầu nghỉ phép này!'
            ], 403);
        }

        if (in_array($leave_request->status, ['approved', 'rejected'])) {
            return response()->json([
                'message' => 'Không thể chỉnh sửa yêu cầu nghỉ phép vì nó đã được ' . $leave_request->status,
            ], 400);
        }

        $result = $this->checkLeaveConstraints(Auth::user()->employee->id,
            $request->leave_type_id, $request->start_date, $request->end_date);
        if (!$result['status']) {
            return response()->json(['message' => $result['message']], 400);
        }

        $leave_requestData = [
            'start_date'    => $request->start_date,
            'end_date'      => $request->end_date,
            'reason'        => $request->reason,
            'leave_type_id' => $request->leave_type_id,
        ];

        $leave_request->update($leave_requestData);

        return response()->json([
            'message' => 'Yêu cầu nghỉ phép được cập nhật thành công',
            'leave_request' => $leave_request
        ], 200);
    }

    public function destroy(string $id)
    {
        $leave_request = LeaveRequest::findOrFail($id);

        if($leave_request->employee->id !== Auth::user()->employee->id){
            return response()->json([
                'message' => 'Bạn không có quyền xóa yêu cầu nghỉ phép không tìm thấy!'
            ], 403);
        }
        // if ($leave_request->status != 'pending') {
        if (in_array($leave_request->status, ['approved', 'rejected'])) {
            return response()->json([
                'message' => 'Không thể xóa yêu cầu nghỉ phép vì nó đã được ' . $leave_request->status,
            ], 400);
        }
        $leave_request->delete();
        return response()->json([
            'message' => 'Yêu cầu nghỉ phép được xóa thành công'
        ], 200);
    }

    public function approveOrReject(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected'
        ]);

        return DB::transaction(function () use ($request, $id) {
            $leave_request = LeaveRequest::findOrFail($id);

            if ($leave_request->status !== 'pending') {
                return response()->json([
                    'message' => 'Yêu cầu nghỉ phép này đã được ' . $leave_request->status . '.',
                ], 400);
            }

            $leave_request->status = $request->status;
            $leave_request->approved_by = Auth::user()->employee->id;
            $leave_request->save();

            if ($leave_request->status == 'approved' && !is_null($leave_request->leave_type->max_days)) {
                $leave_type = $leave_request->leave_type;
                $leave_balance = LeaveBalance::where('employee_id', $leave_request->employee_id)
                    ->where('leave_type_id', $leave_type->id)
                    ->where('year', now()->year)
                    ->first();

                if ($leave_balance) {
                    $daysRequested = $this->calculateDays($leave_request->start_date, $leave_request->end_date);
                    $leave_balance->remaining_days -= $daysRequested;
                    $leave_balance->save();
                } else {
                    return response()->json([
                        'message' => 'Không tìm thấy số dư nghỉ phép cho nhân viên và loại nghỉ phép này.',
                    ], 404);
                }
            }
            // Tạm bỏ qua
            // Mail::to($leave_request->send_by->user->email)->send(new LeaveRequestApprovedMail($leave_request));
            return response()->json([
                'message' => 'Yêu cầu nghỉ phép đã được ' . $request->status . ' thành công.',
                'leave_request' => $leave_request,
            ], 200);
        });
    }

    public function export() 
    {
        return new LeaveRequestExport(Employee::all());
    }

    private function validateLeaveRequest(Request $request)
    {
        $rules = [
            'reason'         => 'nullable|string|max:255',
            'start_date'     => 'required|date|after_or_equal:today',
            'end_date'       => 'required|date|after_or_equal:start_date',
            'leave_type_id'   => 'exists:leave_types,id',
        ];

        $request->validate($rules);
    }

    private function mergeDateFields(Request $request)
    {
        $request->merge([
            'start_date' => DateHelper::toDateFormat($request->start_date),
            'end_date' => DateHelper::toDateFormat($request->end_date),
        ]);
    }

    private function checkLeaveConstraints($employeeId, $leaveTypeId, $start_date, $end_date)
    {
        $daysRequested = $this->calculateDays($start_date, $end_date);
        $leaveType = LeaveType::findOrFail($leaveTypeId);

        if (!is_null($leaveType->max_days) && $daysRequested > $leaveType->max_days) {
            return ['status' => false, 'message' => 'Số ngày yêu cầu vượt quá giới hạn tối đa cho loại nghỉ phép này.'];
        }

        $leaveBalance = LeaveBalance::where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', now()->year)
            ->first();

        if ($leaveBalance && $daysRequested > $leaveBalance->remaining_days) {
            return ['status' => false, 'message' => 'Bạn không có đủ số ngày nghỉ phép còn lại.'];
        }

        return ['status' => true, 'daysRequested' => $daysRequested, 'leaveBalance' => $leaveBalance];
    }

    private function calculateDays($start_date, $end_date)
    {
        return Carbon::parse($start_date)->diffInDays(Carbon::parse($end_date)) + 1;
    }
    
    public function markAbsence(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        if ($user->hasRole('manager')) {
            $department = Department::where('manager_id', $employee->id)->first();
            if (!$department) {
                return response()->json(['message' => 'Bạn không phải là quản lý của phòng ban nào.'], 403);
            }

            $targetEmployee = Employee::find($request->employee_id);
            if (!$targetEmployee || $targetEmployee->department_id !== $department->id) {
                return response()->json(['message' => 'Bạn không có quyền đánh dấu vắng mặt cho nhân viên này.'], 403);
            }
        }
        $this->mergeDateFields($request);
        $request->validate([
            'employee_id'   => 'required|exists:employees,id', 
            'reason'         => 'nullable|string|max:255',
            'start_date'     => 'required|date|after_or_equal:today',
            'end_date'       => 'required|date|after_or_equal:start_date',
            'leave_type_id'  => 'nullable|exists:leave_types,id',
        ]);
        return DB::transaction(function () use ($request, $user) {
            $leave_type_id = isset($request->leave_type_id) ? $request->leave_type_id : 1;
            $leaveRequest = LeaveRequest::create([
                'start_date'    => $request->start_date,
                'end_date'      => $request->end_date,
                'reason'        => $request->reason ?? 'Vắng mặt không thông báo',
                'status'        => 'approved', 
                'employee_id'   => $request->employee_id,
                'leave_type_id' => $leave_type_id,
                'approved_by'   => $user->employee->id,
            ]);

            $leaveBalance = LeaveBalance::where('employee_id', $request->employee_id)
            ->where('leave_type_id', $leave_type_id)
            ->where('year', now()->year)
            ->first();
            $daysRequested = $this->calculateDays($request->start_date, $request->end_date);

            $leaveBalance->remaining_days -= $daysRequested;
            $leaveBalance->save();
            return response()->json([
                'message'  => 'Vắng mặt của nhân viên đã được đánh dấu thành công.',
                'leave_request' => $leaveRequest
            ], 201);
        });
    }

}