<?php

namespace App\Http\Controllers;

use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PositionController extends Controller implements HasMiddleware{

    public static function middleware(): array
    {
        return [
            new Middleware('permission:view-position', only: ['index', 'show']),
            new Middleware('permission:create-position', only: ['store']),
            new Middleware('permission:edit-position', only: ['update']),
            new Middleware('permission:delete-position', only: ['destroy']),
        ];
    }
    
    public function index()
    {
        return response()->json([
            Position::all()
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
        $newPosition = Position::create([
            'name' => $request->name,
            'description' => $request->description
        ]);
        return response()->json([
            'message' => 'Position added successfully',
            'position' => $newPosition
        ], 200);
    }

    public function show(string $id)
    {
        $position = Position::findOrFail($id);
        return response()->json([
            'data' => $position
        ], 200);
    }

    public function edit(string $id)
    {
        
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);
        $position = Position::findOrFail($id);
        $position->update([
            'name' => $request->input('name')
        ]);
        return response()->json([
            'message' => 'Position updated successfully',
            'position' => $position
        ], 200);

    }
    public function destroy(string $id)
    {
        $position = Position::findOrFail($id);
        $position->delete();
        return response()->json([
            'message' => 'Position deleted successfully'
        ], 200);
    }
}