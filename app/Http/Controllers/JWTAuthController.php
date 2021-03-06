<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class JWTAuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|string|min:8|max:255|confirmed',
            'password_confirmation' => 'required|string|min:8|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'messages' => $validator->messages() //발리데이션 실패 요인을 알려줌
            ], 403);
        }

        $user = new User();
        $user->fill($request->all());
        $user->password = bcrypt($request->password);
        $user->save();

        return response()->json([
            'status' => 'success',
            'data' => $user,
        ], 200);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'messages' => $validator->messages(),
            ], 403);
        }

        if (!$token = Auth::guard('api')->attempt([
            'email' => $request->email,
            'password' => $request->password
        ])) {
            return response()->json([
                'error' => 'Unauthorized'
            ], 401);
        }
        //로그인에 성공하면 토큰 반환
        return $this->respondWithToken($token);
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
        ]);
    }

    public function user()
    {
        return response()->json(Auth::guard('api')->user()); //로그인한 유저의 정보를 얻어 응답
    }

    //jwt토큰 갱신
    public function refresh()
    {
        return $this->respondWithToken(Auth::guard('api')->refresh());
    }

    public function logout()
    {
        Auth::guard('api')->logout();

        return response()->json([
            'status' => 'success',
            'message' => 'logout',
        ], 200);
    }
}
