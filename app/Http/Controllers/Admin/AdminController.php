<?php
// app/Http/Controllers/Admin/AdminController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Manager;
use Illuminate\Http\Request;
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
            'email'      => 'required|email',
            'name'       => 'nullable|string|max:255',
            'password'   => 'required|string|min:6|max:255',
            'tier'       => 'required|in:free,plus,pro',
            'tier_until' => 'nullable|date|after:today',
        ]);

        try {
            $fbUser = $this->firebaseAuth->createUser([
                'email'         => $data['email'],
                'emailVerified' => false,
                'password'      => $data['password'],
                'displayName'   => $data['name'] ?? null,
            ]);
        } catch (EmailExists $e) {
            return back()->withErrors(['email' => 'Ya existe un usuario con ese email en Firebase.'])->withInput();
        }

        Manager::updateOrCreate(
            ['firebase_uid' => $fbUser->uid],
            [
                'email'      => $data['email'],
                'name'       => $data['name'] ?? null,
                'tier'       => $data['tier'],
                'tier_until' => $data['tier_until'] ?? null,
                'role'       => 'manager',
            ]
        );

        // The admin controls the password manually and shares it via WhatsApp.
        // Surface the credentials once so they can be copied / sent.
        return back()
            ->with('new_manager_email', $data['email'])
            ->with('new_manager_password', $data['password'])
            ->with('status', 'Manager creado en Firebase. Comparte las credenciales con el manager.');
    }

    public function updateManager(Request $request, Manager $manager)
    {
        $data = $request->validate([
            'tier'       => 'required|in:free,plus,pro',
            'tier_until' => 'nullable|date',
        ]);

        $manager->update([
            'tier'       => $data['tier'],
            'tier_until' => $data['tier_until'] ?? null,
        ]);

        return back()->with('status', "Plan de {$manager->email} actualizado.");
    }
}
