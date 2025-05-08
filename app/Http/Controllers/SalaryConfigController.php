<?php

namespace App\Http\Controllers;

use App\Models\SalaryConfig;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SalaryConfigController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view-salary-config', only: ['index', 'show']),
            new Middleware('permission:create-salary-config', only: ['store']),
            new Middleware('permission:edit-salary-config', only: ['update']),
            new Middleware('permission:delete-salary-config', only: ['destroy']),
        ];
    }

    public function index()
    {
        return response()->json([
            SalaryConfig::all(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);
        $salaryConfig = SalaryConfig::create($data);
        return response()->json([
            'message' => 'Salary config created successfully',
            'data' => $salaryConfig
        ], 201);
    }

    public function show(string $id)
    {
        $config = SalaryConfig::findOrFail($id);
        return response()->json([
            'data' => $config
        ], 200);
    }

    public function update(Request $request, string $id)
    {
        $config = SalaryConfig::findOrFail($id);
        
        $data = $this->validateRequest($request);
        $config->update($data);
        return response()->json([
            'message' => 'Salary config updated successfully',
            'data' => $config
        ]);
    }

    public function destroy(string $id)
    {
        $config = SalaryConfig::findOrFail($id);
        $config->delete();
        return response()->json(['message' => 'Salary config deleted successfully']);
    }

    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'base_salary' => 'required|numeric|min:0',
            'allowance' => 'nullable|numeric|min:0',
            'position_id' => 'required|exists:positions,id',
        ]);
    }

}