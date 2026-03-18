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
    // Login de usuario
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6'
        ]);

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'message' => 'Credenciales inválidas'
            ], 401);
        }

        return $this->responseWithToken($token);
    }

    // Registro de usuario
    public function register(Request $request)
    {
        // El frontend envía 'confirm' en lugar de 'password_confirmation'
        $request->merge([
            'password_confirmation' => $request->confirm
        ]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        // Asignar rol de cliente por defecto
        $user->assignRole('CLIENTE');

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'Usuario registrado exitosamente',
            'user' => $user->load('roles'),
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ], 201);
    }

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

    // Login con Google (desde Firebase)
    public function loginWithGoogle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tokenId' => 'required|string',
            'email' => 'required|email',
            'name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Buscar o crear usuario con el email de Google
            $user = User::updateOrCreate(
                ['email' => $request->email],
                [
                    'name' => $request->name,
                    'google_id' => $request->sub ?? 'firebase-' . $request->email,
                    'password' => Hash::make(str()->random(16))
                ]
            );

            if (!$user->hasRole('CLIENTE')) {
                $user->assignRole('CLIENTE');
            }

            $token = JWTAuth::fromUser($user);

            return response()->json([
                'access_token' => $token,
                'token_type' => 'bearer',
                'user' => $user->load('roles'),
                'expires_in' => auth()->factory()->getTTL() * 60
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al autenticar con Google',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
