<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Carbon\Carbon;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin()
    {
        return $this->role === 'ADMIN';
    }

    public function isUser()
    {
        return $this->role === 'USER';
    }

    public function isDefaultAdmin()
    {
        return $this->email === 'admin@gmail.com';
    }

    // Updated method with default admin privileges
    public function canBeEditedBy($currentUser)
    {
        // Non-admin users can't edit anyone
        if (!$currentUser->isAdmin()) {
            return false;
        }

        // Default admin can edit anyone except themselves
        if ($currentUser->isDefaultAdmin()) {
            return $this->id !== $currentUser->id;
        }

        // Regular admin users can edit regular users
        if ($this->isUser()) {
            return true;
        }

        // Regular admin users cannot edit other admin users (including default admin)
        if ($this->isAdmin() && $this->id !== $currentUser->id) {
            return false;
        }

        // Users can edit themselves (but with restrictions)
        return $this->id === $currentUser->id;
    }

    // Updated method with default admin role change privileges
    public function canChangeRoleTo($newRole, $currentUser)
    {
        // Default admin can change anyone's role except their own
        if ($currentUser->isDefaultAdmin() && $this->id !== $currentUser->id) {
            return true;
        }

        // Users cannot change their own role from ADMIN to USER (except default admin can change others)
        if ($this->id === $currentUser->id && $this->isAdmin() && $newRole === 'USER') {
            return false;
        }

        // Regular admins can only change role of USER role users, not other admins
        if (!$currentUser->isDefaultAdmin() && $this->isAdmin() && $this->id !== $currentUser->id) {
            return false;
        }

        return true;
    }

    // Updated password visibility method
    public function passwordVisibleTo($currentUser)
    {
        // Default admin can see all passwords except their own
        if ($currentUser->isDefaultAdmin() && $this->id !== $currentUser->id) {
            return true;
        }

        // Regular admins can see passwords of USER role users only
        if ($currentUser->isAdmin() && $this->isUser()) {
            return true;
        }

        return false;
    }

    // New method to check if user can be deleted by current user
    public function canBeDeletedBy($currentUser)
    {
        // Check admin permission
        if (!$currentUser->isAdmin()) {
            return false;
        }

        // Prevent deletion of default admin
        if ($this->isDefaultAdmin()) {
            return false;
        }

        // Prevent self-deletion
        if ($this->id === $currentUser->id) {
            return false;
        }

        // Default admin can delete any user (except themselves and default admin)
        if ($currentUser->isDefaultAdmin()) {
            return true;
        }

        // Regular admins cannot delete other admins
        if ($this->isAdmin()) {
            return false;
        }

        return true;
    }
}
