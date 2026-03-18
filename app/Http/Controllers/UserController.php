<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request)
    {
        try {
            $query = User::with('roles');

            if ($request->buscar) {
                $query->where('name', 'like', '%' . $request->buscar . '%')
                      ->orWhere('email', 'like', '%' . $request->buscar . '%');
            }

            $users = $query->orderBy('id', 'desc')->get();

            return response()->json($users, 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener la lista de usuarios.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified user.
     */
    public function show($id)
    {
        try {
            $user = User::with('roles')->findOrFail($id);

            return response()->json($user, 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Usuario no encontrado.',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the role of the specified user.
     */
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'role' => 'required|string|exists:roles,name',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Datos inválidos.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $role = $request->role;
            $user->syncRoles([$role]);

            return response()->json([
                'message' => 'Rol actualizado correctamente.',
                'user' => $user->load('roles')
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el rol.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
