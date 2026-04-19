<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'display_name' => 'sometimes|string|max:100',
            'full_name'    => 'nullable|string|max:150',
            'phone'        => 'nullable|string|max:20',
            'photo'        => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if (isset($data['email']) && $data['email'] !== $user->email) {
            $data['email_verified_at'] = null;
        }

        if ($request->hasFile('photo')) {
            if ($user->photo) {
                Storage::disk('public')->delete(str_replace('storage/', '', $user->photo));
            }
            $path = $request->file('photo')->store('profile', 'public');
            $data['photo'] = 'storage/' . $path;
        }

        $user->update($data);
        return redirect('/profile')->with('success', 'Profil berhasil diperbarui.');
    }

    public function deletePhoto(Request $request)
    {
        $user = auth()->user();
        if ($user->photo) {
            Storage::disk('public')->delete(str_replace('storage/', '', $user->photo));
            $user->update(['photo' => null]);
        }

        return back()->with('success', 'Foto profil dihapus.');
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'password' => 'Password yang diberikan tidak cocok.',
            ], 'userDeletion');
        }

        auth()->logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}