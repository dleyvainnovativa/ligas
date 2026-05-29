<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Manager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;

class AuthController extends Controller
{
    public function __construct(private FirebaseAuth $firebaseAuth) {}

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function sessionLogin(Request $request)
    {
        $request->validate(['id_token' => 'required|string']);

        try {
            // $verified = $this->firebaseAuth->verifyIdToken($request->id_token);
            $verified = $this->firebaseAuth->verifyIdToken(
                $request->id_token,
                true,
                300
            );
        } catch (FailedToVerifyToken $e) {
            return response()->json(['error' => 'Invalid token', "message" => $e->getMessage()], 401);
        }

        $manager = Manager::updateOrCreate(
            ['firebase_uid' => $verified->claims()->get('sub')],
            [
                'email'         => $verified->claims()->get('email'),
                'name'          => $verified->claims()->get('name'),
                'avatar_url'    => $verified->claims()->get('picture'),
                'last_login_at' => now(),
            ]
        );

        Auth::login($manager, remember: true);
        $request->session()->regenerate();

        return response()->json(['redirect' => route('dashboard')]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
