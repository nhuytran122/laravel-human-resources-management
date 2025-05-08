<?php

namespace App\Http\Controllers;

use App\Exports\EmployeeExport;
use App\Helpers\DateHelper;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\User;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller implements HasMiddleware{

    public static function middleware(): array
    {
        return [
            new Middleware('permission:view-employee', only: ['index', 'show']),
            new Middleware('permission:create-employee', only: ['store']),
            new Middleware('permission:edit-employee', only: ['update']),
            new Middleware('permission:delete-employee', only: ['destroy']),
        ];
    }
    
    public function index()
    {
        return response()->json([
            Employee::all()
        ]);
    }
    
    public function create()
    {
        
    }

    public function store(Request $request)
    {
        $this->mergeDateFields($request);
        $this->validateEmployee($request); 
        $existingUser = User::where('email', $request->email)->first();
        if ($existingUser) {
            return response()->json(['message' => 'Email is already exists.'], 400);
        }
        $selectedRoles = Role::whereIn('id', $request->roles)->pluck('name')->toArray();
        if (in_array('manager', $selectedRoles)) {
            $existingManager = Department::where('id', $request->department_id)
                ->whereNotNull('manager_id')
                ->first();
            if ($existingManager) {
                return response()->json(['message' => 'This department already has a manager.'], 400);
            }
        }
        return DB::transaction(function () use ($request, $selectedRoles) {
            $avatarPath = null;
            $cvPath = null;
            $contractPath = null;

            // if ($request->hasFile('avatar')) {
            //     $avatarPath = $this->uploadFile($request->file('avatar'), 'images', $request->full_name);
            // }

            // if ($request->hasFile('cv')) {
            //     $cvPath = $this->uploadFile($request->file('cv'), 'cvs', $request->full_name);
            // }

            // if ($request->hasFile('contract')) {
            //     $contractPath = $this->uploadFile($request->file('contract'), 'contracts', $request->full_name);
            // }

            $user = User::create([
                'name'     => $request->full_name,
                'email'    => $request->email,
                'password' => Hash::make(config('custom.default_password')),
            ]);

            $newEmployee = Employee::create([
                'full_name'     => $request->full_name,
                'gender'        => $request->gender,
                'date_of_birth' => $request->date_of_birth,
                'phone'         => $request->phone,
                'address'       => $request->address,
                'hire_date'     => $request->hire_date,
                'position_id'   => $request->position_id,
                'department_id' => $request->department_id,
                'user_id'       => $user->id,
            ]);

            // $avatarPath = $request->file('avatar');
            // $newEmployee->addMedia($avatarPath)->toMediaCollection('contracts');
            $newEmployee->addMediaFromRequest('avatar')->toMediaCollection('avatars');
            $newEmployee->addMediaFromRequest('cv')->toMediaCollection('cvs');            
            $newEmployee->addMediaFromRequest('contract')->toMediaCollection('contracts');
            
            $selectedRoles[] = 'employee'; 
            $user->assignRole(array_unique($selectedRoles)); 

            if (in_array('manager', $selectedRoles)) {
                $department = $newEmployee->department;
                if ($department) {
                    $department->manager_id = $newEmployee->id;
                    $department->save();
                }
            }

            $currentYear = now()->year;
            $leaveTypes = LeaveType::whereNotNull('max_days')->get();

            foreach ($leaveTypes as $type) {
                if ($type->applicable_gender !== 'all' && $type->applicable_gender !== $request->gender) {
                    continue;
                }
                LeaveBalance::create([
                    'employee_id'    => $newEmployee->id,
                    'leave_type_id'  => $type->id,
                    'remaining_days' => $type->max_days,
                    'year'           => $currentYear,
                ]);
            }

            return response()->json([
                'message'  => 'Employee added successfully',
                'employee' => $newEmployee
            ], 201);
        });
    }
    
    public function show(string $id)
    {
        $employee = Employee::findOrFail($id);
        return response()->json([
            'data' => $employee,
            'media' => $employee->getMedia('avatars')
        ], 200);
    }

    public function edit(string $id)
    {
        
    }

    public function update(Request $request, string $id)
    {
        $this->mergeDateFields($request);
        $this->validateEmployee($request, true);  

        $employee = Employee::findOrFail($id);

        $selectedRoles = Role::whereIn('id', $request->roles)->pluck('name')->toArray();

        if (in_array('manager', $selectedRoles)) {
            $existingManager = Department::where('id', $request->department_id)
                ->whereNotNull('manager_id')
                ->first();

            if ($existingManager && $existingManager->manager_id !== $employee->id) {
                return response()->json(['message' => 'This department already has a manager.'], 400);
            }
        }

        $employeeData = [
            'full_name'     => $request->full_name,
            'gender'        => $request->gender,
            'date_of_birth' => $request->date_of_birth,
            'phone'         => $request->phone,
            'address'       => $request->address,
            'hire_date'     => $request->hire_date,
            'position_id'   => $request->position_id,
            'department_id' => $request->department_id,
        ];

        $employee->update($employeeData);
        $mediaCollections = [
            'avatar' => 'avatars',
            'cv' => 'cvs',
            'contract' => 'contracts',
        ];

        foreach ($mediaCollections as $input => $collection) {
            if ($request->hasFile($input)) {
                $employee->clearMediaCollection($collection);
                $employee->addMediaFromRequest($input)
                        ->withCustomProperties(['uploaded_at' => now()])
                        ->toMediaCollection($collection);
                
            }
        }
        $selectedRoles[] = 'employee';
        if ($employee->user) {
            $employee->user->syncRoles(array_unique($selectedRoles));
        }

        $department = $employee->department;
        if ($department) {
            if (in_array('manager', $selectedRoles)) {
                $department->manager_id = $employee->id;
            } else if ($department->manager_id === $employee->id) {
                // Nếu trước đó là manager nhưng bị gỡ role
                $department->manager_id = null;
            }
            $department->save();
        }

        return response()->json([
            'message' => 'Employee updated successfully',
            'employee' => $employee
        ], 200);
    }
    
    public function destroy(string $id)
    {
        $employee = Employee::findOrFail($id);
        $hasSalary = $employee->salaries()->exists();

        if ($hasSalary) {
            return response()->json([
                'message' => 'Cannot delete employee because this employee has salary records.'
            ], 400);
        }
        $employee->delete();
        // $employee->clearMediaCollections();
        return response()->json([
            'message' => 'Employee deleted successfully'
        ], 200);
    }

    public function export() 
    {
        return new EmployeeExport();
    }

    public function createFromJobApplication($job_application_id)
    {
        $application = JobApplication::findOrFail($job_application_id);

        return response()->json([
            'suggested_employee_data' => [
                'full_name' => $application->full_name,
                'gender' => $application->gender,
                'date_of_birth' => $application->date_of_birth,
                'phone' => $application->phone,
                'address' => $application->address,
                'email' => $application->email,
            ],
            'departments' => Department::all(['id', 'name']),
            'positions' => Position::all(['id', 'name']),
        ]);
    }


    private function validateEmployee(Request $request, $isUpdate = false)
    {
        $rules = [
            'email'         => 'required|string|email',
            'full_name'     => 'required|string|max:255',
            'gender'        => 'required|string|in:female,male,other',
            'date_of_birth' => 'required|date',
            'phone' => [
                'required',
                'regex:/(?:\+84|0084|0)[235789][0-9]{1,2}[0-9]{7}(?:[^\d]+|$)/'
            ],
            'address'       => 'required|string|max:255',
            'hire_date'     => 'required|date',
            'avatar'        => 'file|image|mimes:jpeg,png,jpg|max:5048',
            'cv'            => 'file|mimes:pdf,doc,docx|max:5048',
            'contract'      => 'file|mimes:pdf,doc,docx|max:5048',
            'position_id'   => 'exists:positions,id',
            'department_id' => 'exists:departments,id',
            'roles'         => 'required|array|exists:roles,id',
        ];

        if ($isUpdate) {
            // Bỏ validation cho email vì email không thay đổi
            unset($rules['email']);
        }

        $request->validate($rules);
    }


    private function mergeDateFields(Request $request)
    {
        $request->merge([
            'hire_date' => DateHelper::toDateFormat($request->hire_date),
            'date_of_birth' => DateHelper::toDateFormat($request->date_of_birth),
        ]);
    }

    private function uploadFile($file, $folder, $name)
    {
        $fileName = time() . '-' . str_replace(' ', '_', $name) . '.' . $file->extension();
        $file->move(public_path($folder), $fileName);
        return $folder . '/' . $fileName;
    }
}