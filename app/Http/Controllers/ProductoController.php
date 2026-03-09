<?php

namespace App\Http\Controllers;

use App\Models\Imagen;
use App\Models\Producto;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $productos = Producto::with(['marca', 'categoria', 'imagenes']);

            if($request->buscar){
            $query->where('nombre','like','%'.$request->buscar.'%');
            }
               $productos = $query -> orderBy('id', 'desc')
                ->get();

            return response()->json($productos, 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener la lista de productos.',
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {

            if (! $request->has('producto')) {
                return response()->json([
                    'message' => 'El objeto producto es requerido',
                ], 422);
            }
            // Decodificamos el JSON
            $productoData = json_decode($request->producto, true);

            if (! $productoData) {
                return response()->json([
                    'message' => 'El formato del JSON es inválido',
                ], 422);
            }
            // Normalizar estructura
            $data = [
                'nombre' => $productoData['nombre'] ?? null,
                'descripcion' => $productoData['descripcion'] ?? null,
                'precio' => $productoData['precio'] ?? null,
                'stock' => $productoData['stock'] ?? null,
                'modelo' => $productoData['modelo'] ?? null,
                'marca_id' => $productoData['marca']['id'] ?? null,
                'categoria_id' => $productoData['categoria']['id'] ?? null,
                'activo' => $productoData['activo'] ?? null,
            ];

            
            $validator = Validator::make($data, [
                'nombre' => 'required|string|max:80|unique:productos,nombre',
                'descripcion' => 'required|string|max:200',
                'precio' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'modelo' => 'required|string|max:50',
                'marca_id' => 'required|exists:marcas,id',
                'categoria_id' => 'required|exists:categorias,id',
                'activo' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            
            DB::beginTransaction();

           
            $producto = Producto::create($data);
            
            if ($request->hasFile('imagenes')) {
                
                foreach ($request->file('imagenes') as $file) {
                    
                    $nombreImagen = uniqid().'_'.$file->getClientOriginalName();
                    $rutaDestino = public_path('images/productos');
                    // si no existe la carpeta la creamos
                    if (! file_exists($rutaDestino)) {
                        mkdir($rutaDestino, 0755, true);
                    }
                    // copiamos el archivo a la ruta destino
                    $file->move($rutaDestino, $nombreImagen);
                   
                    Imagen::create([
                        'nombre' => $nombreImagen,
                        'producto_id' => $producto->id,
                    ]);
                }
            }
            
            DB::commit();
            // obtenemos el objeto guardado completo
            $producto->load(['marca', 'categoria', 'imagenes']);

            return response()->json([
                'message' => 'Producto registrado correctamente',
                'producto' => $producto,
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $producto = Producto::with(['marca', 'categoria', 'imagenes'])->findOrFail($id);

            return response()->json($producto);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'No se ha encontrado el producto con ID = '.$id,
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
           
            $producto = Producto::with('imagenes')->findOrFail($id);
            
            if (! $request->has('producto')) {
                return response()->json([
                    'message' => 'El objeto producto es requerido',
                ], 422);
            }
            
            $productD = json_decode($request->producto, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'message' => 'El JSON enviado en producto no es válido',
                    'error' => json_last_error_msg(),
                ], 422);
            }
           
            $validator = Validator::make($productD, [
                'nombre' => 'required|string|max:80|unique:productos,nombre,'.$id,
                'descripcion' => 'required|string|max:200',
                'precio' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'modelo' => 'required|string|max:50',
                'marca.id' => 'required|exists:marcas,id',
                'categoria.id' => 'required|exists:categorias,id',
                'activo' => 'required|boolean',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Existen errores de validación',
                    'error' => $validator->errors(),
                ], 422);
            }
            
            DB::beginTransaction();
            // transformamos datos al formato de base de datos
            $data = [
                'nombre' => $productD['nombre'],
                'descripcion' => $productD['descripcion'],
                'precio' => $productD['precio'],
                'stock' => $productD['stock'],
                'modelo' => $productD['modelo'],
                'marca_id' => $productD['marca']['id'],
                'categoria_id' => $productD['categoria']['id'],
                'activo' => $productD['activo'],
            ];
            
            $producto->update($data);
            
            if ($request->hasFile('imagenes')) {
                // eliminamos fisicamente las imagenes anteriores
                foreach ($producto->imagenes as $img) {
                    $ruta = public_path('images/productos/'.$img->nombre);
                    if (file_exists($ruta)) {
                        unlink($ruta); // borramos el archivo físico
                    }
                    
                    $img->delete();
                }
                
                foreach ($request->file('imagenes') as $file) {
                    // cambiamos el nombre de la imagen
                    $nombreImagen = time().'_'.$file->getClientOriginalName();
                    $rutaDestino = public_path('images/productos');

                    if (! file_exists($rutaDestino)) {
                        mkdir($rutaDestino, 0755, true);
                    }

                    $file->move($rutaDestino, $nombreImagen);
                    
                    Imagen::create([
                        'nombre' => $nombreImagen,
                        'producto_id' => $producto->id,
                    ]);
                }
            }
            
            DB::commit();

            return response()->json([
                'message' => 'Producto actualizado correctamente',
                'producto' => $producto->load(['marca', 'categoria', 'imagenes']),
            ], 202);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'No se encuentra el producto con ID = '.$id,
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al actualizar el producto',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            
            $producto = Producto::with('imagenes')->findOrFail($id);

            DB::beginTransaction();

            
            foreach ($producto->imagenes as $img) {
                $ruta = public_path('images/productos/'.$img->nombre);

                if (file_exists($ruta)) {
                    unlink($ruta);
                }

                
                $img->delete();
            }

            $producto->delete();

            DB::commit();

            return response()->json([
                'message' => 'Producto eliminado correctamente',
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'No se encontró el producto con ID = '.$id,
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al eliminar el producto',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
