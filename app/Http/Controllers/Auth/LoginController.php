<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    /** Intentos fallidos permitidos antes de bloquear temporalmente. */
    private const MAX_INTENTOS = 5;

    /** Segundos que dura el bloqueo tras agotar los intentos. */
    private const SEGUNDOS_BLOQUEO = 60;

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

        // 3. Límite de intentos por cuenta + IP (evita probar contraseñas sin fin)
        $llave = $this->llaveIntentos($credentials['email'], $request);
        if (RateLimiter::tooManyAttempts($llave, self::MAX_INTENTOS)) {
            $segundos = RateLimiter::availableIn($llave);

            return back()->withErrors([
                'email' => "Demasiados intentos fallidos. Espera {$segundos} segundos e inténtalo de nuevo.",
            ])->onlyInput('email');
        }

        // 4. Revisar la contraseña SIN iniciar sesión todavía
        if (!Auth::validate($credentials)) {
            RateLimiter::hit($llave, self::SEGUNDOS_BLOQUEO);

            return back()->withErrors([
                'email' => 'Las credenciales ingresadas no coinciden con nuestros registros.',
            ])->onlyInput('email');
        }

        /** @var User $user */
        $user = Auth::getProvider()->retrieveByCredentials($credentials);

        // 5. El personal marcado como "Inactivo" ya no puede entrar
        if ($user->estaInactivo()) {
            return back()->withErrors([
                'email' => 'Tu cuenta está inactiva. Pide a un administrador que la reactive.',
            ])->onlyInput('email');
        }

        // 6. Iniciar sesión
        RateLimiter::clear($llave);
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
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

    private function llaveIntentos(string $email, Request $request): string
    {
        return 'login:' . Str::transliterate(Str::lower($email)) . '|' . $request->ip();
    }
}
