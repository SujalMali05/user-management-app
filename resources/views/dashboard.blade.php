@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">
            <i class="fas fa-tachometer-alt text-primary mr-3"></i>
            Dashboard
            @if(auth()->user()->isDefaultAdmin())
                <span class="text-yellow-600 text-lg ml-2">
                    <i class="fas fa-crown"></i> Super Admin
                </span>
            @endif
        </h1>
        <p class="mt-2 text-gray-600">Manage all registered users in the system</p>
        @if(auth()->user()->isDefaultAdmin())
            <p class="mt-1 text-sm text-yellow-600">
                <i class="fas fa-info-circle mr-1"></i>
                As default admin, you have full access to manage all users including other admins.
            </p>
        @endif
    </div>

    <div class="bg-white shadow-xl rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-users mr-2"></i>
                Registered Users ({{ $users->count() }})
            </h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        @if(auth()->user()->isAdmin())
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Password</th>
                        @endif
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                        @if(auth()->user()->isAdmin())
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($users as $user)
                    <tr class="hover:bg-gray-50 transition duration-200">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-10 w-10 bg-gradient-to-r from-blue-400 to-purple-500 rounded-full flex items-center justify-center">
                                    <span class="text-white font-semibold text-sm">
                                        {{ substr($user->name, 0, 1) }}
                                    </span>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $user->name }}
                                        @if($user->isDefaultAdmin())
                                            <span class="ml-2 px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">
                                                <i class="fas fa-shield-alt mr-1"></i>Default Admin
                                            </span>                                            
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $user->email }}</div>
                        </td>
                        @if(auth()->user()->isAdmin())
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($user->passwordVisibleTo(auth()->user()))
                                <span class="text-xs font-mono bg-green-100 px-2 py-1 rounded text-green-800">••••••••</span>
                            @else
                                <span class="text-xs text-gray-400 px-2 py-1">
                                    <i class="fas fa-lock mr-1"></i>Protected
                                </span>
                            @endif
                        </td>
                        @endif
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($user->isDefaultAdmin())
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    <i class="fas fa-crown mr-1"></i>SUPER ADMIN
                                </span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $user->isAdmin() ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' }}">
                                    <i class="fas {{ $user->isAdmin() ? 'fa-user-shield' : 'fa-user' }} mr-1"></i>
                                    {{ $user->role }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div class="flex flex-col">
                                <span class="font-medium">
                                    {{ $user->created_at->setTimezone('Asia/Kolkata')->format('M j, Y') }}
                                </span>
                                <span class="text-xs text-gray-400">
                                    {{ $user->created_at->setTimezone('Asia/Kolkata')->format('g:i A') }} IST
                                </span>
                            </div>
                        </td>
                        @if(auth()->user()->isAdmin())
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-2">
                                @if($user->canBeEditedBy(auth()->user()))
                                    <a href="{{ route('users.edit', $user) }}" 
                                       class="text-blue-600 hover:text-blue-900 transition duration-200"
                                       title="Edit User">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @else
                                    <span class="text-gray-300" title="{{ $user->id === auth()->id() ? 'Cannot edit yourself' : 'Cannot edit this user' }}">
                                        <i class="fas fa-edit"></i>
                                    </span>
                                @endif
                                
                                @if($user->canBeDeletedBy(auth()->user()))
                                    <form method="POST" action="{{ route('users.destroy', $user) }}" 
                                          onsubmit="return confirm('Are you sure you want to delete this user?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 transition duration-200"
                                                title="Delete User">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-gray-300" title="Cannot delete this user">
                                        <i class="fas fa-trash"></i>
                                    </span>
                                @endif
                            </div>
                        </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
