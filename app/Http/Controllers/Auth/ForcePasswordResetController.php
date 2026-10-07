<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ForcePasswordResetController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the force password reset view.
     */
    public function show(): View|RedirectResponse
    {
        $user = Auth::user();

        if (! $user->must_reset_password) {
            return redirect()->route('executors.index');
        }

        return view('auth.passwords.force-reset', compact('user'));
    }

    /**
     * Update the user's password from temporary to permanent.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'current_password.current_password' => 'The provided temporary password does not match our records.',
            'password.different' => 'The new password must be different from the temporary password.',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
            'must_reset_password' => false,
        ]);

        return redirect()->route('executors.index')
            ->with('success', "Security requirement satisfied: Password updated successfully. Welcome to PlanTrack, {$user->name}!");
    }
}
