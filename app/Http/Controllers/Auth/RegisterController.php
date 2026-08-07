<?php
// app/Http/Controllers/Auth/RegisterController.php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Manager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;

class RegisterController extends Controller
{
    public function __construct(private FirebaseAuth $firebaseAuth) {}

    public function show()
    {
        if (Auth::check()) return redirect()->route('dashboard');
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate(['id_token' => 'required|string']);

        try {
            $verified = $this->firebaseAuth->verifyIdToken($request->id_token, false, 5);
        } catch (\Kreait\Firebase\Exception\Auth\RevokedIdToken $e) {
            return response()->json(['error' => 'Token recién emitido. Reintenta.', 'code' => 'TOKEN_STALE'], 401);
        } catch (\Kreait\Firebase\Exception\Auth\FailedToVerifyToken $e) {
            return response()->json(['error' => 'Token inválido.'], 401);
        }

        $manager = Manager::updateOrCreate(
            ['firebase_uid' => $verified->claims()->get('sub')],
            [
                'email'         => $verified->claims()->get('email'),
                'name'          => $verified->claims()->get('name'),
                'avatar_url'    => $verified->claims()->get('picture'),
                'last_login_at' => now(),
                // tier defaults to 'free', role defaults to 'manager'
            ]
        );

        Auth::login($manager, remember: true);
        $request->session()->regenerate();

        return response()->json(['redirect' => route('dashboard')]);
    }
}
