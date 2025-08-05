<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function edit(User $user)
    {
        // Check if current user can edit this user
        if (!$user->canBeEditedBy(auth()->user())) {
            $message = auth()->user()->isDefaultAdmin() 
                ? 'You cannot edit your own account details.' 
                : 'You do not have permission to edit this user.';
            return redirect()->route('dashboard')->with('error', $message);
        }

        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        // Check if current user can edit this user
        if (!$user->canBeEditedBy(auth()->user())) {
            $message = auth()->user()->isDefaultAdmin() 
                ? 'You cannot edit your own account details.' 
                : 'You do not have permission to edit this user.';
            return redirect()->route('dashboard')->with('error', $message);
        }

        // Validate basic fields
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        ];

        // Add role validation only if role can be changed
        if ($user->canChangeRoleTo($request->role, auth()->user())) {
            $rules['role'] = 'required|in:ADMIN,USER';
        } else {
            // If role cannot be changed, remove it from request
            $request->request->remove('role');
        }

        $validatedData = $request->validate($rules);

        $updateData = [
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
        ];

        // Add role only if it was validated and can be changed
        if (isset($validatedData['role']) && $user->canChangeRoleTo($validatedData['role'], auth()->user())) {
            $updateData['role'] = $validatedData['role'];
        }

        // Handle password update
        if ($request->filled('password')) {
            $request->validate(['password' => 'min:8']);
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        return redirect()->route('dashboard')->with('success', 'User updated successfully!');
    }

    public function destroy(User $user)
    {
        // Use the new canBeDeletedBy method
        if (!$user->canBeDeletedBy(auth()->user())) {
            if ($user->isDefaultAdmin()) {
                return redirect()->route('dashboard')->with('error', 'Cannot delete the default admin user.');
            } elseif ($user->id === auth()->id()) {
                return redirect()->route('dashboard')->with('error', 'You cannot delete your own account!');
            } elseif (!auth()->user()->isDefaultAdmin() && $user->isAdmin()) {
                return redirect()->route('dashboard')->with('error', 'Only default admin can delete other admin users.');
            } else {
                return redirect()->route('dashboard')->with('error', 'Unauthorized access.');
            }
        }

        $user->delete();
        return redirect()->route('dashboard')->with('success', 'User deleted successfully!');
    }
}
