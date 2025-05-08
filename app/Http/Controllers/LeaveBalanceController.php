<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\LeaveBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveBalanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'message' => 'Không tìm thấy thông tin nhân viên.'
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
            $leaveRequests = LeaveBalance::whereIn('employee_id', $employeeIds)->get();
        } else {
            if ($user->hasRole('admin') || $user->hasRole('hr')) {
                $leaveRequests = LeaveBalance::all();
            } else {
                $leaveRequests = LeaveBalance::where('employee_id', $user->employee->id)->get();
            }
        }

        return response()->json($leaveRequests, 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(LeaveBalance $leaveBalance)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LeaveBalance $leaveBalance)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LeaveBalance $leaveBalance)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LeaveBalance $leaveBalance)
    {
        //
    }
}