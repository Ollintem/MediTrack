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
            return redirect()->route('home');
        }

        return view('auth.login');
    }

    /**
     * Procesa la solicitud de autenticación.
     */
    public function login(Request $request)
    {
        // 1. Validar los campos de entrada
        $request->validate([
            'email'    => 'required|string',
            'password' => 'required|string|min:8',
        ], [
            'email.required'    => 'Ingresa tu correo o usuario.',
            'password.required' => 'Ingresa tu contraseña.',
        ]);

        $loginInput = trim($request->email);

        // 2. Determinar si se ingresó un correo completo o solo el nombre de usuario
        $credentials = filter_var($loginInput, FILTER_VALIDATE_EMAIL)
            ? ['email' => $loginInput, 'password' => $request->password]
            : ['email' => strtolower($loginInput) . '@meditrack.com', 'password' => $request->password];

        // 3. Intentar autenticar contra la base de datos
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('home'));
        }

        // 4. Si falla, retornar el error a la vista
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