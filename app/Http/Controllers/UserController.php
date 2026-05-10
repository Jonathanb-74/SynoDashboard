<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        $users      = User::orderBy('name')->get();
        $adminCount = $users->where('role', 'admin')->count();
        return view('users.index', compact('users', 'adminCount'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                  => 'required|string|max:191',
            'email'                 => 'required|email|max:191|unique:users,email',
            'role'                  => 'required|in:admin,user',
            'password'              => ['required', Password::min(8)],
            'password_confirmation' => 'required|same:password',
        ]);

        User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'role'     => $data['role'],
            'password' => Hash::make($data['password']),
        ]);

        return back()->with('success', "L'utilisateur « {$data['name']} » a été créé.");
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'                  => 'required|string|max:191',
            'email'                 => 'required|email|max:191|unique:users,email,' . $user->id,
            'role'                  => 'required|in:admin,user',
            'password'              => ['nullable', Password::min(8)],
            'password_confirmation' => 'nullable|same:password',
        ]);

        // Prevent removing the last admin
        if ($user->isAdmin() && $data['role'] === 'user') {
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return back()->withErrors(['role' => 'Impossible de rétrograder le dernier administrateur.']);
            }
        }

        $user->name  = $data['name'];
        $user->email = $data['email'];
        $user->role  = $data['role'];

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return back()->with('success', "L'utilisateur « {$user->name} » a été mis à jour.");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['delete' => 'Vous ne pouvez pas supprimer votre propre compte.']);
        }

        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            return back()->withErrors(['delete' => 'Impossible de supprimer le dernier administrateur.']);
        }

        $name = $user->name;
        $user->delete();

        return back()->with('success', "L'utilisateur « {$name} » a été supprimé.");
    }
}
