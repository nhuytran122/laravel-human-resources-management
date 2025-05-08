<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request){
        $request->validate([
            'name' =>  'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);
        $user = User::create([
            'name' => $request->name,
            'email'=> $request->email,
            'password' => Hash::make($request->password)
        ]);

        $token = Auth::login($user);
        return $this->respondWithToken($token);
    }

    public function login(Request $request){
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        $credential = $request->only('email', 'password');

        $token = Auth::attempt($credential);
        if(!$token){
            return response()->json([
                'status' => 'error',
                'message' => 'Incorrect email or password'
            ], 401);
        }
        $user = Auth::user();
        return $this->respondWithToken($token);
    }

    public function logout(){
        Auth::logout();
        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ]);
    }

    public function refresh(){
        $refresh_token = Auth::refresh();
        return $this->respondWithToken($refresh_token);
    }

    public function profile(){
        return response()->json([
            'account' =>  Auth::user(),
            'information' => Auth::user()->employee,
            'roles' => Auth::user()->roles->pluck('name')->implode(', ')
        ]);
    }

    public function changePassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed', 
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        Auth::logout();
        return response()->json(['message' => 'Password changed successfully. Please login again.']);
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60
        ]);
    }
}