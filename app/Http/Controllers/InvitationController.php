<?php

namespace App\Http\Controllers;

use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'unique:users,email', 'unique:invitations,email'],
            'role'  => ['required', 'in:admin,user'],
        ], [
            'email.unique' => "Un compte ou une invitation existe déjà pour cette adresse email.",
        ]);

        $invitation = Invitation::create([
            'email'               => $request->email,
            'role'                => $request->role,
            'token'               => Str::random(64),
            'invited_by_user_id'  => $request->user()->id,
            'expires_at'          => now()->addHours(72),
            'created_at'          => now(),
        ]);

        Mail::to($invitation->email)->send(new InvitationMail($invitation));

        return redirect()->route('users.index')
            ->with('success', "Invitation envoyée à {$invitation->email}.");
    }

    public function resend(Invitation $invitation): RedirectResponse
    {
        abort_unless($invitation->isPending(), 422, 'Cette invitation est expirée ou déjà utilisée.');

        // Repousser l'expiration de 72h depuis maintenant
        $invitation->update(['expires_at' => now()->addHours(72)]);

        Mail::to($invitation->email)->send(new InvitationMail($invitation));

        return redirect()->route('users.index')
            ->with('success', "Invitation renvoyée à {$invitation->email}.");
    }

    public function destroy(Invitation $invitation): RedirectResponse
    {
        $invitation->delete();

        return redirect()->route('users.index')
            ->with('success', 'Invitation annulée.');
    }

    public function show(string $token): View|RedirectResponse
    {
        $invitation = Invitation::where('token', $token)->first();

        if (!$invitation || !$invitation->isPending()) {
            return redirect()->route('login')
                ->with('status', "Ce lien d'invitation est invalide ou a expiré.");
        }

        return view('auth.accept-invitation', compact('invitation', 'token'));
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = Invitation::where('token', $token)->first();

        if (!$invitation || !$invitation->isPending()) {
            return redirect()->route('login')
                ->with('status', "Ce lien d'invitation est invalide ou a expiré.");
        }

        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $invitation->email,
            'password' => Hash::make($request->password),
            'role'     => $invitation->role,
        ]);

        $invitation->update(['accepted_at' => now()]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
