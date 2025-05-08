<?php

use App\Exports\EmployeeExport;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\InterviewController;
use App\Http\Controllers\InterviewParticipantController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\JobOfferController;
use App\Http\Controllers\JobPostingController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\SalaryConfigController;
use App\Http\Controllers\SalaryController;
use App\Mail\MyEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::post("/job-applications", [JobApplicationController::class, 'store']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware(['auth:api']);
Route::post('/refresh', [AuthController::class, 'refresh'])
    ->middleware(['auth:api']);
Route::get('/profile', [AuthController::class, 'profile'])
    ->middleware(['auth:api']);
Route::post('/change-password', [AuthController::class, 'changePassword'])
    ->middleware(['auth:api']);

Route::get('/employees/export-employees', [EmployeeController::class, 'export']);
Route::get('/leave-requests/export-leave-requests', [LeaveRequestController::class, 'export']);

Route::resource('positions', PositionController::class);
Route::resource('departments', DepartmentController::class);
Route::resource('employees', EmployeeController::class);

Route::resource('leave-types', LeaveTypeController::class);
Route::resource('leave-requests', LeaveRequestController::class);

Route::group(['middleware' => ['role:finance|admin']], function () {
    Route::resource('salaries', SalaryController::class);
    Route::resource('salary-configs', SalaryConfigController::class);
});

Route::group(['middleware' => ['role:hr|manager|admin']], function () {
    Route::post('/leave-requests/{id}/approval', [LeaveRequestController::class, 'approveOrReject']);
    Route::post('/leave-requests/mark-absence', [LeaveRequestController::class, 'markAbsence']);

    // Route::get('/employees/export-employees', [EmployeeController::class, 'export']);
    // Route::get('/leave-requests/export-leave-requests', [LeaveRequestController::class, 'export']);
});

Route::group(['middleware' => ['role:hr|admin']], function () {
    Route::get('/attendances', [AttendanceController::class, 'index']);
    Route::post('/attendances', [AttendanceController::class, 'create']);
    Route::resource('job-postings', JobPostingController::class);

    Route::get('/job-applications', [JobApplicationController::class, 'index']);
    Route::get('/job-applications/{id}', [JobApplicationController::class, 'show']);
    Route::put('/job-applications', [JobApplicationController::class, 'update']);
    Route::delete('/job-applications', [JobApplicationController::class, 'destroy']);

    Route::post('/job-applications/{id}/handle', [JobApplicationController::class, 'handleJobApplication']);

    Route::post('/interviews', [InterviewController::class, 'store']);
    Route::put('/interviews/{id}', [InterviewController::class, 'update']);
    Route::delete('/interviews/{id}', [InterviewController::class, 'destroy']);
    Route::put('/interviews/{id}/result', [InterviewController::class, 'updateResult']);
    

    Route::get('/interviews/create/{job_application_id}', [InterviewController::class, 'create'])
    ->name('interviews.create');

    Route::get('/job-applications/{job_application_id}/offers/create', [JobOfferController::class, 'create'])->name('job-offers.create');
    
    Route::post('/job-offers', [JobOfferController::class, 'store'])->name('job-offers.store');
    Route::put('/job-offers/{id}', [JobApplicationController::class, 'update']);
    Route::patch('/job-offers/{id}/update-status', [JobOfferController::class, 'updateStatus']);

    Route::get('/employees/create-from-job-app/{id}', [EmployeeController::class, 'createFromJobApplication'])
        ->name('employees.create-from-job-app');;


});

Route::get('/interviews', [InterviewController::class, 'index']);
Route::get('/interviews/{id}', [InterviewController::class, 'show']);
Route::put('/interviews/{id}/feedback', [InterviewController::class, 'updateFeedback'])
    ->middleware(['auth:api']);

Route::get("/test-email", function(){
    $name = 'Nhu Y';
    Mail::to('nhuyvinmini1218@gmail.com')->send(new MyEmail($name));
});