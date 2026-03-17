<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class AuthController extends Controller
{
    public function me(){
    return response()->json(auth()->user());
    }

    //método para invalidar un token (logout)
    public function logout(){
    auth()->logout();
    return response()->json([
        'message' => 'Sesión cerrada correctamente'
    ]);
    }

    protected function responseWithToken($token){
      return response()->json([
          'access_token' => $token,
          'token_type' => 'bearer',
          'user' => auth()->user()->load('roles'),
          'expires_in' => auth()->factory()->getTTL() * 60
      ]);
     }

    //método para refrescar el token
    public function refresh(){
    return $this->responseWithToken(auth()->refresh());
    }
}
