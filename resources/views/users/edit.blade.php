@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="max-w-2xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center text-primary hover:text-secondary transition duration-200">
            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
        </a>
        <h1 class="text-3xl font-bold text-gray-900 mt-4">
            <i class="fas fa-user-edit text-primary mr-3"></i>
            Edit User
            @if($user->isDefaultAdmin())
                <span class="text-yellow-600 text-lg ml-2">
                    <i class="fas fa-shield-alt"></i> Default Admin
                </span>
            @endif
        </h1>
    </div>

    <div class="bg-white shadow-xl rounded-lg overflow-hidden border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-blue-50">
            <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                <i class="fas fa-user-cog text-primary mr-2"></i>
                User Information
            </h2>
            @if(auth()->user()->isDefaultAdmin() && $user->isAdmin() && !$user->isDefaultAdmin())
                <p class="text-sm text-green-600 mt-2 flex items-center">
                    <i class="fas fa-info-circle mr-1"></i>
                    As default admin, you can edit this admin user's details and role.
                </p>
            @elseif($user->isDefaultAdmin() && auth()->user()->id !== $user->id)
                <p class="text-sm text-yellow-600 mt-2 flex items-center">
                    <i class="fas fa-shield-alt mr-1"></i>
                    This is the default admin user.
                </p>
            @endif
        </div>
        
        <form method="POST" action="{{ route('users.update', $user) }}" class="p-6 space-y-6">
            @csrf
            @method('PUT')
            
            <!-- Full Name Field -->
            <div class="space-y-2">
                <label for="name" class="block text-sm font-semibold text-gray-700 flex items-center">
                    <i class="fas fa-user text-gray-500 mr-2"></i>
                    Full Name
                </label>
                <input type="text" 
                       name="name" 
                       id="name" 
                       value="{{ old('name', $user->name) }}" 
                       required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition duration-200 placeholder-gray-400"
                       placeholder="Enter full name">
                @error('name')
                    <p class="mt-1 text-sm text-red-600 flex items-center">
                        <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                    </p>
                @enderror
            </div>

            <!-- Email Field -->
            <div class="space-y-2">
                <label for="email" class="block text-sm font-semibold text-gray-700 flex items-center">
                    <i class="fas fa-envelope text-gray-500 mr-2"></i>
                    Email Address
                </label>
                <input type="email" 
                       name="email" 
                       id="email" 
                       value="{{ old('email', $user->email) }}" 
                       required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition duration-200 placeholder-gray-400"
                       placeholder="Enter email address">
                @error('email')
                    <p class="mt-1 text-sm text-red-600 flex items-center">
                        <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                    </p>
                @enderror
            </div>

            <!-- Password Field -->
            <div class="space-y-2">
                <label for="password" class="block text-sm font-semibold text-gray-700 flex items-center">
                    <i class="fas fa-lock text-gray-500 mr-2"></i>
                    New Password 
                    <span class="text-gray-500 text-xs font-normal ml-2">(leave blank to keep current password)</span>
                </label>
                <div class="relative">
                    <input type="password" 
                           name="password" 
                           id="password"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition duration-200 placeholder-gray-400"
                           placeholder="Enter new password (optional)">
                    <button type="button" 
                            onclick="togglePassword()" 
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700">
                        <i class="fas fa-eye" id="password-toggle"></i>
                    </button>
                </div>
                @error('password')
                    <p class="mt-1 text-sm text-red-600 flex items-center">
                        <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                    </p>
                @enderror
            </div>

            <!-- Role Field -->
            <div class="space-y-2">
                <label for="role" class="block text-sm font-semibold text-gray-700 flex items-center">
                    <i class="fas fa-user-tag text-gray-500 mr-2"></i>
                    Role
                </label>
                @if($user->canChangeRoleTo('USER', auth()->user()) || $user->canChangeRoleTo('ADMIN', auth()->user()))
                    <select name="role" 
                            id="role" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition duration-200">
                        <option value="USER" {{ old('role', $user->role) === 'USER' ? 'selected' : '' }}>
                            USER - Regular User Access
                        </option>
                        <option value="ADMIN" {{ old('role', $user->role) === 'ADMIN' ? 'selected' : '' }}>
                            ADMIN - Administrative Access
                        </option>
                    </select>
                    @if(auth()->user()->isDefaultAdmin() && $user->isAdmin())
                        <p class="text-xs text-green-600 mt-1 flex items-center">
                            <i class="fas fa-check mr-1"></i>You can change this admin user's role
                        </p>
                    @endif
                @else
                    <div class="w-full px-4 py-3 bg-gray-100 border border-gray-300 rounded-lg text-gray-600 flex items-center">
                        <i class="fas {{ $user->isAdmin() ? 'fa-crown' : 'fa-user' }} mr-2"></i>
                        <span class="font-medium">{{ $user->role }}</span>
                        <span class="text-xs text-gray-500 ml-2">(Cannot be changed)</span>
                    </div>
                    <input type="hidden" name="role" value="{{ $user->role }}">
                @endif
                @error('role')
                    <p class="mt-1 text-sm text-red-600 flex items-center">
                        <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                    </p>
                @enderror
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                <a href="{{ route('dashboard') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition duration-200">
                    <i class="fas fa-times mr-2"></i>
                    Cancel
                </a>
                <button type="submit" 
                        class="inline-flex items-center px-6 py-2 bg-gradient-to-r from-primary to-secondary text-white font-semibold rounded-lg hover:from-secondary hover:to-primary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition duration-200 shadow-lg transform hover:scale-105">
                    <i class="fas fa-save mr-2"></i>
                    Update User
                </button>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript for password toggle -->
<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('password-toggle');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}
</script>
@endsection
