<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $attendances = Attendance::where('month', $request->month ?? now()->month())
            ->where('year', $request->year ?? now()->year())
            ->get();

        return response()->json([
            'data' => $attendances
        ], 200);
    }

    public function create(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $month = $request->month ?? now()->month();
                $year = $request->year ?? now()->year();
                $beginOfMonth = Carbon::createFromDate($year, $month, 1)->startOfDay();
                $endOfMonth = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();

                $now = Carbon::now();
                if ($now->lt($endOfMonth)) {
                    return response()->json([
                        'message' => 'Chỉ tổng kết ngày công sau khi hết tháng'
                    ], 400);
                }

                $workingDaysInMonth = $this->calculateWorkingDaysInMonth($beginOfMonth, $endOfMonth);

                // Query sum ngày nghỉ theo nhân viên
                $leaveSummary = DB::table('leave_requests')
                    ->join('leave_types', 'leave_requests.leave_type_id', '=', 'leave_types.id')
                    ->select(
                        'leave_requests.employee_id',
                        DB::raw('SUM(CASE WHEN leave_types.is_paid = 0 THEN DATEDIFF(leave_requests.end_date, leave_requests.start_date) + 1 ELSE 0 END) AS unpaid_leave_days'),
                        DB::raw('SUM(CASE WHEN leave_types.is_paid = 1 THEN DATEDIFF(leave_requests.end_date, leave_requests.start_date) + 1 ELSE 0 END) AS paid_leave_days')
                    )
                    ->where('leave_requests.status', 'approved')
                    ->where(function ($query) use ($beginOfMonth, $endOfMonth) {
                        $query->whereBetween('leave_requests.start_date', [$beginOfMonth, $endOfMonth])
                              ->orWhereBetween('leave_requests.end_date', [$beginOfMonth, $endOfMonth]);
                    })
                    ->groupBy('leave_requests.employee_id')
                    ->get()
                    ->keyBy('employee_id');

                Employee::chunk(100, function ($employees) use ($month, $year, $workingDaysInMonth, $leaveSummary) {
                    foreach ($employees as $employee) {
                        $this->calculateWorkingDaysForEmployee($employee->id, $month, $year, $workingDaysInMonth, $leaveSummary);
                    }
                });

                $attendances = Attendance::where('month', $month)
                    ->where('year', $year)
                    ->get();

                return response()->json([
                    'message' => 'Tổng kết ngày công thành công',
                    'data' => $attendances
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    protected function calculateWorkingDaysInMonth($beginOfMonth, $endOfMonth)
    {
        $workingDaysInMonth = 0;
        foreach ($beginOfMonth->copy()->daysUntil($endOfMonth) as $date) {
            if ($date->isWeekday()) {
                $workingDaysInMonth++;
            }
        }
        return $workingDaysInMonth;
    }

    protected function calculateWorkingDaysForEmployee($employeeId, $month, $year, $workingDaysInMonth, $leaveSummary)
    {
        $existingAttendance = Attendance::where('employee_id', $employeeId)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if ($existingAttendance) {
            return;
        }

        $summary = $leaveSummary->get($employeeId);
        $unpaidLeaveDays = $summary ? (int) $summary->unpaid_leave_days : 0;
        $paidLeaveDays = $summary ? (int) $summary->paid_leave_days : 0;

        Attendance::create([
            'employee_id' => $employeeId,
            'month' => $month,
            'year' => $year,
            'working_days' => $workingDaysInMonth - $unpaidLeaveDays,
            'leave_days' => $unpaidLeaveDays,
            'paid_leave_days' => $paidLeaveDays,
        ]);
    }
}