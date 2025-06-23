<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Check if the current user is an admin.
     */
    private function ensureAdmin(Request $request): void
    {
        if (!$request->user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }
    }

    /**
     * Display a listing of the users.
     */
    public function index(Request $request): Response
    {
        $this->ensureAdmin($request);
        
        $users = User::orderBy('created_at', 'desc')->paginate(10);

        return Inertia::render('manage-users/ManageUsers', [
            'users' => $users,
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(Request $request): Response
    {
        $this->ensureAdmin($request);
        
        return Inertia::render('manage-users/CreateUser');
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'is_admin' => 'boolean',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_admin' => $request->boolean('is_admin'),
        ]);

        return redirect()->route('manage-users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(Request $request, User $user): Response
    {
        $this->ensureAdmin($request);
        
        return Inertia::render('manage-users/EditUser', [
            'user' => $user,
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'is_admin' => ['required', 'boolean'],
        ]);

        $user->update($validated);

        return redirect('/manage-users')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin($request);

        // Prevent admin from deleting themselves
        if ($request->user()->id === $user->id) {
            return redirect('/manage-users')->with('error', 'You cannot delete yourself.');
        }

        $user->delete();

        return redirect('/manage-users')->with('success', 'User deleted successfully.');
    }

    /**
     * Toggle admin status for a user.
     */
    public function toggleAdmin(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin($request);
        
        // Prevent admin from removing their own admin status
        if ($user->id === auth()->id()) {
            return redirect()->route('manage-users.index')
                ->with('error', 'You cannot modify your own admin status.');
        }

        $user->update(['is_admin' => !$user->is_admin]);

        $status = $user->is_admin ? 'granted admin privileges' : 'removed admin privileges';

        return redirect()->route('manage-users.index')
            ->with('success', "User {$status} successfully.");
    }
}
