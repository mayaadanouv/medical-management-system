<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class UserController extends Controller
{
    public function register( Request $request)
    {
        $request->validate([
            'name'=>'required|string',
            'email'=>'required_without:phone|string|email|unique:users,email|nullable|required_if:type_user,doctor',
            'phone'=>'required_without:email|string|unique:users,phone|nullable|required_if:type_user,doctor',
            'password'=>'required|string|min:8|confirmed',
            'type_user'=>'required|string|in:patient,doctor,admin'
        ]);
        $user=User::create([
            'name'=>$request->name,
            'email'=>$request->email,
            'phone'=>$request->phone,
            'password'=>Hash::make($request->password),
            'type_user'=>$request-> type_user
        ]);
        $token = $user->createToken('register_token')->plainTextToken;
        return response()->json([
            'success' => true,
            'message'=>'User Registered Successfully',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user)
            ],201);
    }
    public function login(Request $request)
    {
        $request->validate([
            'email'=>'required_without:phone|string',
            'phone'=>'required_without:email|string',
            'password'=>'required|string',]);
        $data=$request->has('email') ? 'email':'phone';
        if(!Auth::attempt($request->only($data,'password')))
            {
                return response()->json([
                    'success' => false,
                    'message'=>'invalid'
                    ], 401);
            }
        $user=User::where($data,$request->$data)->firstOrFail();
        $token=$user->createToken('auth_Token')->plainTextToken;
        return response()->json([
            'success' => true,
            'message'=>'login Successfully',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user)
        ], 200);
    }
    public function logout(Request $request)
    {
     /** @var \Laravel\Sanctum\PersonalAccessToken $token */

        $token = $request->user()->currentAccessToken();
        if ($token)
            {
                $token->delete();
            }
        return response()->json([
            'success' => true,
            'message'=>'logout Successfully'
        ],200);
    }
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);
        $user = $request->user();
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The current password is incorrect'
            ], 400);
        }
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);
        return response()->json([
            'success' => true,
            'message' => 'The password has been successfully changed'
        ], 200);
    }
}
