<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ClienteController extends Controller
{
    public function store(Request $request)
    {
        // Validación de los datos entrantes para registro
        $validator = Validator::make($request->all(), [
            'nombre'   => 'required|string|max:255',
            'email'    => 'required|email|unique:clientes,email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        // Creación del nuevo cliente, con contraseña encriptada
        $cliente = Cliente::create([
            'nombre'   => $request->nombre,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Generar token para autenticación
        $token = $cliente->createToken('auth_token')->plainTextToken;

        return response()->json([
            'mensaje' => 'Cliente creado correctamente',
            'cliente' => $cliente,
            'token'   => $token
        ], 201);
    }

    public function login(Request $request)
    {
        // Validación de los datos entrantes para login
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        // Buscar el cliente por email
        $cliente = Cliente::where('email', $request->email)->first();

        // Verificar que el cliente exista y que la contraseña sea correcta
        if (!$cliente || !Hash::check($request->password, $cliente->password)) {
            return response()->json([
                'errors' => ['credenciales' => ['Credenciales incorrectas']]
            ], 401);
        }

        // Generar token para autenticación
        $token = $cliente->createToken('auth_token')->plainTextToken;

        return response()->json([
            'mensaje' => 'Login exitoso',
            'cliente' => $cliente,
            'token'   => $token
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'mensaje' => 'Cierre de sesión exitoso'
        ], 200);
    }
}
