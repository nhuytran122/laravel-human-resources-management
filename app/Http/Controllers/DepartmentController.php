<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DepartmentController extends Controller implements HasMiddleware{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view-department', only: ['index', 'show']),
            new Middleware('permission:create-department', only: ['store']),
            new Middleware('permission:edit-department', only: ['update']),
            new Middleware('permission:delete-department', only: ['destroy']),
        ];
    }
    public function index()
    {
        return response()->json([
            Department::all()
        ]);
    }
    
    public function create()
    {
        
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $newDepartment = Department::create([
            'name' => $request->name,
            'description' => $request->description
        ]);
        return response()->json([
            'message' => 'Department added successfully',
            'department' => $newDepartment
        ], 200);
    }

    public function show(string $id)
    {
        $department = Department::findOrFail($id);
    }

    public function edit(string $id)
    {
        
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);
        $department = Department::findOrFail($id);
        $department->update([
            'name' => $request->input('name')
        ]);
        return response()->json([
            'message' => 'Department updated successfully',
            'department' => $department
        ], 200);

    }
    public function destroy(string $id)
    {
        $department = Department::findOrFail($id);
        $department->delete();
        return response()->json([
            'message' => 'Department deleted successfully'
        ], 200);
    }
}