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
            return redirect()->route('home'); // Redirige al inicio/dashboard si ya hay sesión
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

        // 2. Formatear correo si ingresaron solo el nombre de usuario
        $inputEmail = strtolower(trim($request->email));

        if (!str_contains($inputEmail, '@')) {
            $inputEmail .= '@meditrack.com';
        }

        // 3. Credenciales incluyendo verificación explícita del estado "Activo"
        $credentials = [
            'email'    => $inputEmail,
            'password' => $request->password,
            'estado'   => 'Activo',
        ];

        // 4. Intentar autenticar
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            // Actualizar fecha/hora del último inicio de sesión
            $user = Auth::user();
            $user->ultimo_login = now();
            $user->save();

            // Redirigir de forma neutral al Dashboard general
            return redirect()->intended(route('home'));
        }

        // 5. Si falla, regresar con mensaje explícito
        return back()->withErrors([
            'email' => 'Las credenciales ingresadas no coinciden o la cuenta está inactiva.',
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