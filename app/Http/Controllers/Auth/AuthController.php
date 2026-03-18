<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    //Este controlador se deben programar los metodos para la gestion de usuarios

    //Metodo para autenticacion
    public function login(Request $request){
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6'
        ]);
        //evaluamos si no se obtiene un token válido
        if(!$token = JwtAuth::attempt($credenciales)){
           return response()->json([
            'message'=> 'Credenciales inválidas'
           ], 401);
        }
        //en caso de exitoso retornamos el token
        return $this->responseWithToken($token);
    }

    //Registro de usuarios
    public function register(Request $request){

            $request->merge([
            'password_confirmation' => $request->confirm
        ]);
      //validamos datos a través de Request
      $validator = Validator::make($request->all(),[
          'name' => 'required|string|max:50',
          'email' => 'required|string|email|max:191|unique:users',
          'password' => 'required|string|min:8'
      ]);
      if($validator->fails()){
          return response()->json($validator->errors(),422);
      }
      //creamos el usuario
      $user = User::create([
          'name' => $request->name,
          'email' => $request->email,
          'password' => Hash::make($request->password)
      ]);

      //Recordatorio--Asignar rol por defecto
        $user->assignRole('CLIENTE');

      //generamos el token
      $token = JWTAuth::fromUser($user);
      //retornamos la respuesta

      return response()->json([
          'message' => 'Usuario registrado correctamente',
          'user' => $user,
          'access_token' => $token,
          'token_type' => 'bearer',
           'expires_in' => auth()->factory()->getTTL() * 60
      ],201);
  }

 protected function responseWithToken($token){
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'user' => auth()->user()->load('roles'),
             'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }

  //metodo para obtener un usuario autenticado
    public function me(){
        return response()->json(auth()->user());
    }

    //metodo para invalidar token
    public function logout(){
        auth()->logout();
        return response()->json([
            'message' => 'Sesión cerrada exitosamente'
        ]);
    }

    //metodo para refrescar token
    public function refresh(){
        return $this->responseWithToken(auth()->refresh());
    }
}
