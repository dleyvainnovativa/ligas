<?php
// app/Http/Controllers/Admin/AdminController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Manager;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\Auth\EmailExists;

class AdminController extends Controller
{
    public function __construct(private FirebaseAuth $firebaseAuth) {}

    public function index()
    {
        return view('admin.dashboard', [
            'managerCount' => Manager::where('role', 'manager')->count(),
            'adminCount' => Manager::where('role', 'admin')->count(),
            'byTier' => Manager::selectRaw('tier, COUNT(*) as n')->groupBy('tier')->pluck('n', 'tier'),
        ]);
    }

    public function managers()
    {
        return view('admin.managers', [
            'managers' => Manager::orderByDesc('created_at')->paginate(25),
        ]);
    }

    public function storeManager(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'name' => 'nullable|string|max:255',
            'tier' => 'required|in:free,plus,pro',
            'tier_until' => 'nullable|date|after:today',
        ]);

        $tempPassword = Str::random(16);

        try {
            $fbUser = $this->firebaseAuth->createUser([
                'email' => $data['email'],
                'emailVerified' => false,
                'password' => $tempPassword,
                'displayName' => $data['name'] ?? null,
            ]);
        } catch (EmailExists $e) {
            return back()->withErrors(['email' => 'Ya existe un usuario con ese email en Firebase.'])->withInput();
        }

        // Trigger Firebase's password-reset email so the manager sets their own password.
        $resetLink = $this->firebaseAuth->getPasswordResetLink($data['email']);

        Manager::updateOrCreate(
            ['firebase_uid' => $fbUser->uid],
            [
                'email' => $data['email'],
                'name' => $data['name'] ?? null,
                'tier' => $data['tier'],
                'tier_until' => $data['tier_until'] ?? null,
                'role' => 'manager',
            ]
        );

        return back()->with('status', "Manager creado. Enlace para establecer contraseña: {$resetLink}");
    }
}
