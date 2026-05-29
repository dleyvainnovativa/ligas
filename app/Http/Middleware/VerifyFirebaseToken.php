<?php

namespace App\Http\Middleware;

use App\Models\Manager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Symfony\Component\HttpFoundation\Response;

class VerifyFirebaseToken
{
    public function __construct(private FirebaseAuth $firebaseAuth) {}

    public function handle(Request $request, Closure $next): Response
    {
        $idToken = $this->extractToken($request);

        if (!$idToken) {
            return $this->unauthorized($request, 'Missing Firebase token.');
        }

        try {
            $verified = $this->firebaseAuth->verifyIdToken($idToken);
        } catch (FailedToVerifyToken $e) {
            return $this->unauthorized($request, 'Invalid Firebase token.');
        }

        $uid    = $verified->claims()->get('sub');
        $email  = $verified->claims()->get('email');
        $name   = $verified->claims()->get('name');
        $avatar = $verified->claims()->get('picture');

        $manager = Manager::updateOrCreate(
            ['firebase_uid' => $uid],
            [
                'email'         => $email,
                'name'          => $name,
                'avatar_url'    => $avatar,
                'last_login_at' => now(),
            ]
        );

        Auth::login($manager);
        $request->setUserResolver(fn() => $manager);

        return $next($request);
    }

    private function extractToken(Request $request): ?string
    {
        if ($bearer = $request->bearerToken()) {
            return $bearer;
        }
        return $request->input('id_token');
    }

    private function unauthorized(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => $message], 401);
        }
        return redirect()->route('login')->withErrors(['auth' => $message]);
    }
}
