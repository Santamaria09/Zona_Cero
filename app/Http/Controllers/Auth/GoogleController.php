<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;


class GoogleController extends Controller
{
        public function redirect()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function callback()
    {

       try {

            $googleUser = Socialite::driver('google')
                ->stateless()
                ->setHttpClient(new \GuzzleHttp\Client([
                    'verify' => false
                ]))
                ->user();


              if (!$googleUser->getEmail()) {
                return response()->json([
                    'errro' => 'Email no encontrado'
                ],422);
            }

       }catch (\Exception $e) {
        return response()->json([
        'message'=> 'Error al autenticar con google',
        'error' => $e->getMessage()

        ], 500);

       }

        $user = User::updateOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name' => $googleUser->getName(),
                'google_id' => $googleUser->getId(),
                'password' => bcrypt(str()->random(16))
            ]
        );

        if(!$user->hasRole('CLIENTE')){
            $user->assignRole('CLIENTE');
        }

        $token = JWTAuth::fromUser($user);
        return response()->json([
            'token'=> $token,
            'user'=> $user
        ]);
    }
}
