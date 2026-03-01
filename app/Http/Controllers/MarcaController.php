<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MarcaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
        $categoria = Categoria::all(); 
        return response()->json($categoria);
        
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
             $request->validate([
            'nombre' => 'required|string|min:2|max:60|unique:categorias,nombre',
        ], [
            'nombre.required' => 'El nombre de la categoría es obligatorio',
            'nombre.string'   => 'El nombre de la categoría debe ser una cadena de texto',
            'nombre.min'      => 'El nombre de la categoría debe tener al menos 2 caracteres',
            'nombre.max'      => 'El nombre de la categoría no debe exceder los 60 caracteres',
            'nombre.unique'  => 'El nombre de la categoría ya existe'
        ]);

        $categoria = Categoria::create($validated);
        return response()->json([
            'message' => 'Categoría registrada correctamente',
            'categoria' => $categoria
        ], 201);

        } catch(\Exception $e) {
            return response()->json([
                'message' => 'Error, no se pudo registrar la categoría',
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
            $categoria =Categoria::findOrFail($id);
            return response()->json($categoria);
        } catch(\Exception $e) {
            return response()->json([
                'message' => 'Error, no se encontró la categoría',
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
            $categoria = Categoria::findOrFail($id);
            $request->validate(
                [
                    'nombre'=> ['required', 'string', 'min:2', 'max:60', 
                    Rule::unique('categorias', 'nombre')->ignore($id)
                    ]
                ],
                [
                    'nombre.unique'=> 'El nombre de la categoría ya existe'
                ]
            );
            $categoria->update([
                'nombre'=>$request->nombre
            ]);
            return response()->json([
                'message' => 'Categoria actualizada correctamente',
                'categoria'=>$categoria
            ],201);
        } catch(\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la categoría',
                'error' => $e->getMessage()
            ], 500); 
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    { 
        try{
            $categoria = Categoria::with('productos')->findOrFail($id);

            if ($categoria->productos->exists())
            {
                return response()->json([
                    'message' => 'No te puede eliminar esta categoria porque tiene productos asociados'
                ],409);
            }

            $categoria->delete();
            return response()->json([
                'message' => 'Categoria eliminada correctamente'
            ],200);
            
        }catch(ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Error, no se encontró la categoría con ID: ' . $id
            ], 404);

        }
    }
}


