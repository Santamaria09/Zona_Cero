<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Categoria;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CategoriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
        $categorias = Categoria::all(); 
        return response()->json($categorias);
        
        } catch(\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las categorias',
                'error' => $e->getMessage()
            ], 500); 
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
         try {
        // validamos a nivel request
        $validated = $request->validate([
            'nombre' => 'required|string|min:5|max:80|unique:categorias,nombre',
        ], [
            'nombre.required' => 'El nombre de la categoría es requerido',
            'nombre.string'   => 'El nombre de la categoría debe ser una cadena de texto',
            'nombre.min'      => 'El nombre de la categoría debe tener al menos 5 caracteres',
            'nombre.max'      => 'El nombre de la categoría no debe exceder los 80 caracteres',
            'nombre.unique'  => 'El nombre de la categoría ya existe'
        ]);

        // insertamos la categoria en la base de datos
        $categoria = Categoria::create($validated);

        return response()->json([
            'message'   => 'Categoria registrada exitosamente',
            'categoria' => $categoria
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
        $categoria = Categoria::findOrFail($id); 
        return response()->json($categoria);
        
        } catch(\Exception $e) {
            return response()->json([
                'message' => 'Erro inesperado del servidor',
                'error' => $e->getMessage()
            ], 404); 
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
         try{
             //primero obtenemos el registro de la bd
            $categoria = Categoria::findOrFail($id);
            //aplicamos validaciones a nivel de request
            $request->validate(
                [
                    'nombre' => [
                        'required',
                        'string',
                        'min:5',
                        'max:80',
                        Rule::unique('categorias', 'nombre')->ignore($id)
                    ]
                ],
                [
                    'nombre.unique' => 'Ya existe una categoria con este nombre en la base de datos'
                ]
            );

            //mandamos a actualizar el registro
            $categoria->update([
                'nombre' =>$request->nombre
            ]);
            return response()->json([
                'message' => 'Categoria actualizada correctamente',
                'categoria' => $categoria
            ],202);    
            }catch(\Exception $e){
                return response()->json([
                    'message' => 'Categoria no encontrada',
                    'error' => $e->getMessage()
                ],500);
            }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $categoria = Categoria::with('productos')->findOrFail($id);

            if ($categoria->productos()->exists()) {
                return response()->json([
                    'message' => 'No se puede eliminar la categoria porque tiene productos asociados.'
                ], 409);
            }

            $categoria->delete();

            return response()->json([
                'message' => 'Categoria eliminada correctamente.'
            ], 200);

            } catch (ModelNotFoundException $e) {
                return response()->json([
                    'message' => 'Categoria no encontrada, con el ID = ' .$id
                ], 404);
            }
    
    }
}
