<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Muestra la vista de inicio de sesión.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('personal.index');
        }

        return view('auth.login');
    }

    /**
     * Procesa la solicitud de autenticación.
     */
    public function login(Request $request)
    {
        // 1. Validar que vengan los datos requeridos
        $request->validate([
            'email'    => 'required|string',
            'password' => 'required|string|size:8',
        ], [
            'email.required'    => 'Ingresa tu correo o usuario.',
            'password.required' => 'Ingresa tu contraseña.',
            'password.size'     => 'La contraseña debe tener exactamente 8 caracteres.',
        ]);

        // 2. Formatear correo si ingresaron solo el usuario
        $inputEmail = strtolower(trim($request->email));

        if (!str_contains($inputEmail, '@')) {
            $inputEmail .= '@meditrack.com';
        }

        $credentials = [
            'email'    => $inputEmail,
            'password' => $request->password,
        ];

        // 3. Intentar autenticar
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            // Redirigir de forma explícita a la ruta nombrada de personal
            return redirect()->route('personal.index');
        }

        // 4. Si falla, regresar con error
        return back()->withErrors([
            'email' => 'Las credenciales ingresadas no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    /**
     * Cierra la sesión activa.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}