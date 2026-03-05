<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ToadAuthService;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
//use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Auth\ToadUser;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/home';
    protected $toadAuth;

    public function __construct(ToadAuthService $toadAuth)
    {
        $this->middleware('guest')->except('logout');
        $this->toadAuth = $toadAuth;
    }

    public function login(Request $request)
    {
        $this->validateLogin($request);

        $resp = $this->toadAuth->verify(
            $request->input('username'),
            $request->input('password')
        );

        if (!$resp) {
            throw ValidationException::withMessages([
                'username' => [trans('auth.failed')],
            ]);
        }

        $username = $request->input('username');

        $userData = [
            'id'        => $username,
            'email'     => null,
            'name'      => $username,
            'token'     => $resp['token'] ?? null,
            'staff'     => ['username' => $username],
        ];

        // Enregistrer l'utilisateur en session
        $request->session()->put('toad_user', $userData);

        // Connecter un utilisateur "en mémoire"
        $user = new ToadUser($userData);
        Auth::login($user, false); // éviter remember me (non supporté par ce provider)

        return $this->sendLoginResponse($request);
    }

    protected function validateLogin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);
    }
}
