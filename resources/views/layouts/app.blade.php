<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'User Management')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3B82F6',
                        secondary: '#1E40AF',
                    }
                }
            }
        }
    </script>
    <style>
        .fade-in { animation: fadeIn 0.5s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .slide-in { animation: slideIn 0.3s ease-out; }
        @keyframes slideIn { from { transform: translateX(-100%); } to { transform: translateX(0); } }
        
        /* Toast Notification Styles */
        .toast-container {
            animation: slideInRight 0.4s ease-out;
            max-width: 400px;
            min-width: 320px;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .toast-exit {
            animation: slideOutRight 0.3s ease-in forwards;
        }

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        .toast-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: linear-gradient(to right, #3B82F6, #1E40AF);
            animation: progressBar 5s linear forwards;
        }

        .toast-progress.success {
            background: linear-gradient(to right, #10B981, #059669);
        }

        .toast-progress.error {
            background: linear-gradient(to right, #EF4444, #DC2626);
        }

        @keyframes progressBar {
            from { width: 100%; }
            to { width: 0%; }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    @auth
    <nav class="bg-white shadow-lg border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <h1 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-users text-primary mr-2"></i>
                            User Management
                        </h1>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">
                        <i class="fas fa-user-circle mr-1"></i>
                        {{ auth()->user()->name }}
                        <span class="ml-2 px-2 py-1 text-xs rounded-full {{ auth()->user()->isAdmin() ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' }}">
                            {{ auth()->user()->role }}
                        </span>
                    </span>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-gray-500 hover:text-gray-700 transition duration-200">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>
    @endauth

    <main class="fade-in">
        @yield('content')
    </main>

    <!-- Enhanced Toast Notification System -->
    @if(session('success') || session('error'))
    <div id="toast-notification" class="fixed top-5 right-5 z-50">
        <div class="toast-container bg-white border-l-4 {{ session('success') ? 'border-green-500' : 'border-red-500' }} rounded-lg shadow-xl overflow-hidden relative">
            <!-- Progress Bar -->
            <div class="toast-progress {{ session('success') ? 'success' : 'error' }}"></div>
            
            <div class="p-4 flex items-start">
                <!-- Icon Section -->
                <div class="flex-shrink-0">
                    @if(session('success'))
                        <div class="h-10 w-10 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="h-6 w-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    @else
                        <div class="h-10 w-10 bg-red-100 rounded-full flex items-center justify-center">
                            <svg class="h-6 w-6 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    @endif
                </div>
                
                <!-- Content Section -->
                <div class="ml-3 flex-1">
                    <div class="flex items-center">
                        <p class="text-sm font-semibold text-gray-900">
                            {{ session('success') ? 'Success!' : 'Error!' }}
                        </p>
                    </div>
                    <p class="text-sm text-gray-600 mt-1 leading-relaxed">
                        {{ session('success') ?? session('error') }}
                    </p>
                </div>
                
                <!-- Close Button -->
                <div class="ml-4 flex-shrink-0">
                    <button onclick="hideToast()" class="text-gray-400 hover:text-gray-600 transition-colors duration-200 p-1 rounded-full hover:bg-gray-100">
                        <span class="sr-only">Close</span>
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function hideToast() {
            const toast = document.getElementById('toast-notification');
            if (toast) {
                toast.querySelector('.toast-container').classList.add('toast-exit');
                setTimeout(() => {
                    if (toast && toast.parentNode) {
                        toast.remove();
                    }
                }, 300);
            }
        }

        // Auto-hide toast after 5 seconds
        setTimeout(() => {
            hideToast();
        }, 5000);

        // Optional: Hide toast when clicking outside
        document.addEventListener('click', function(event) {
            const toast = document.getElementById('toast-notification');
            if (toast && !toast.contains(event.target)) {
                // Uncomment the line below if you want to hide on outside click
                // hideToast();
            }
        });

        // Optional: Hide toast with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                hideToast();
            }
        });
    </script>
    @endif
</body>
</html>
