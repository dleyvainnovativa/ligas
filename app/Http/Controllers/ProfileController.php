<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return view('profile.show', [
            'manager' => $request->user(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        // Display name lives on the managers table only. We intentionally do not
        // sync Firebase's displayName — the app reads `name` from this model
        // everywhere, and login only seeds it from Firebase on first sign-in.
        $request->user()->update([
            'name' => $data['name'] ?? null,
        ]);

        return back()->with('success', 'Perfil actualizado.');
    }
}
