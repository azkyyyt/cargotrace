<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'CargoTrace') - Global Shipment Visibility Platform</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0f7ff',
                            100: '#e0effe',
                            500: '#0284c7',
                            600: '#0369a1',
                            700: '#075985',
                            800: '#0c4a6e',
                            900: '#082f49',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Leaflet CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <!-- Alpine.js for interactive dropdowns/modals -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        .leaflet-popup-content-wrapper { border-radius: 0.5rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
    </style>
    @stack('styles')
</head>
<body class="h-full flex flex-col font-sans text-slate-800 antialiased" x-data="{ sidebarOpen: false }">

    <!-- Top Navigation Bar -->
    <header class="bg-slate-900 text-white sticky top-0 z-40 border-b border-slate-800 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Left: Logo & Brand -->
                <div class="flex items-center space-x-3">
                    <button type="button" @click="sidebarOpen = !sidebarOpen" class="md:hidden text-slate-400 hover:text-white p-2">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-2">
                        <div class="bg-gradient-to-tr from-sky-500 to-indigo-600 text-white p-2 rounded-lg font-bold shadow-md">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                            </svg>
                        </div>
                        <span class="text-xl font-extrabold tracking-tight text-white">Cargo<span class="text-sky-400">Trace</span></span>
                    </a>
                    <span class="hidden lg:inline-block text-xs bg-sky-950 text-sky-300 font-semibold px-2 py-0.5 rounded border border-sky-800">UAS Web Programming 2</span>
                </div>

                <!-- Center: Quick Examiner Demo Switcher -->
                <div class="hidden md:flex items-center space-x-2 text-xs">
                    <span class="text-slate-400">Quick Switch:</span>
                    <a href="{{ route('demo.login', 'admin') }}" class="px-2.5 py-1 rounded font-medium {{ Auth::check() && Auth::user()->isAdmin() ? 'bg-amber-500 text-slate-900 font-bold' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">Admin</a>
                    <a href="{{ route('demo.login', 'operator') }}" class="px-2.5 py-1 rounded font-medium {{ Auth::check() && Auth::user()->isOperator() ? 'bg-blue-500 text-white font-bold' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">Operator</a>
                    <a href="{{ route('demo.login', 'customer') }}" class="px-2.5 py-1 rounded font-medium {{ Auth::check() && Auth::user()->isCustomer() ? 'bg-emerald-500 text-white font-bold' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">Customer</a>
                </div>

                <!-- Right: Notifications & User Profile -->
                <div class="flex items-center space-x-3">
                    @auth
                        <!-- Notification Bell -->
                        @php
                            $unreadCount = Auth::user()->inAppNotifications()->where('is_read', false)->count();
                        @endphp
                        <a href="{{ route('notifications.index') }}" class="relative p-2 text-slate-300 hover:text-white rounded-lg hover:bg-slate-800 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            @if($unreadCount > 0)
                                <span class="absolute top-1.5 right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[10px] font-bold text-white">
                                    {{ $unreadCount }}
                                </span>
                            @endif
                        </a>

                        <!-- User Profile Pill -->
                        <div class="flex items-center space-x-2 pl-2 border-l border-slate-700">
                            <div class="text-right hidden sm:block">
                                <div class="text-xs font-semibold text-white">{{ Auth::user()->name }}</div>
                                <div class="text-[10px] uppercase font-bold tracking-wider {{ Auth::user()->isAdmin() ? 'text-amber-400' : (Auth::user()->isOperator() ? 'text-sky-400' : 'text-emerald-400') }}">
                                    {{ Auth::user()->role }}
                                </div>
                            </div>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" title="Logout" class="p-2 text-slate-400 hover:text-rose-400 hover:bg-slate-800 rounded-lg transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="text-sm text-slate-300 hover:text-white">Sign In</a>
                        <a href="{{ route('register') }}" class="text-sm bg-sky-600 hover:bg-sky-500 text-white px-3 py-1.5 rounded-lg font-medium transition">Register</a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Main Container with Sidebar -->
    <div class="flex-1 flex max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 gap-6">

        @auth
        <!-- Desktop Sidebar -->
        <aside class="w-64 flex-shrink-0 hidden md:block">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 sticky top-24 space-y-6">
                <!-- Navigation Menu -->
                <nav class="space-y-1">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-sky-50 text-sky-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="w-5 h-5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span>Dashboard</span>
                    </a>

                    <a href="{{ route('shipments.index') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('shipments.*') ? 'bg-sky-50 text-sky-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="w-5 h-5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <span>Shipments</span>
                    </a>

                    <a href="{{ route('exceptions.index') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('exceptions.*') ? 'bg-sky-50 text-sky-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>Exceptions</span>
                    </a>

                    <a href="{{ route('integrations.index') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('integrations.*') ? 'bg-sky-50 text-sky-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>External APIs</span>
                    </a>

                    <a href="{{ route('notifications.index') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('notifications.*') ? 'bg-sky-50 text-sky-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span>Notifications</span>
                    </a>
                </nav>

                <!-- Admin Management Section -->
                @if(Auth::user()->isAdmin())
                <div class="pt-4 border-t border-slate-100">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2 px-3">Administration</div>
                    <nav class="space-y-1">
                        <a href="{{ route('carriers.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('carriers.*') ? 'bg-amber-50 text-amber-800 font-semibold' : 'text-slate-600 hover:bg-slate-50' }}">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <span>Carriers</span>
                        </a>

                        <a href="{{ route('locations.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('locations.*') ? 'bg-amber-50 text-amber-800 font-semibold' : 'text-slate-600 hover:bg-slate-50' }}">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span>Locations</span>
                        </a>

                        <a href="{{ route('audit.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('audit.*') ? 'bg-amber-50 text-amber-800 font-semibold' : 'text-slate-600 hover:bg-slate-50' }}">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span>Audit Trail</span>
                        </a>
                    </nav>
                </div>
                @endif

                <!-- REST API Quick Documentation Link -->
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-100 text-xs text-slate-600 space-y-1">
                    <div class="font-semibold text-slate-800 flex items-center justify-between">
                        <span>REST API Suite</span>
                        <span class="bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded text-[10px]">Active</span>
                    </div>
                    <p class="text-[11px] text-slate-500">Bearer token enabled via Laravel Sanctum.</p>
                </div>
            </div>
        </aside>
        @endauth

        <!-- Main Content Area -->
        <main class="flex-1 min-w-0">
            <!-- Flash Message Alerts -->
            @if(session('success'))
                <div class="mb-4 bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r-lg shadow-sm flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span class="text-sm font-medium text-emerald-800">{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-rose-50 border-l-4 border-rose-500 p-4 rounded-r-lg shadow-sm flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span class="text-sm font-medium text-rose-800">{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            @if(session('warning'))
                <div class="mb-4 bg-amber-50 border-l-4 border-amber-500 p-4 rounded-r-lg shadow-sm flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span class="text-sm font-medium text-amber-800">{{ session('warning') }}</span>
                    </div>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-lg shadow-sm">
                    <div class="font-semibold text-sm mb-1">Please correct the following errors:</div>
                    <ul class="list-disc list-inside text-xs space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-4 mt-auto text-center text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
            <div>
                <strong>CargoTrace</strong> &copy; 2026 &bull; Web Programming 2 Final Project (UAS)
            </div>
            <div>
                Built with Laravel 11, Tailwind CSS, Leaflet.js, OpenStreetMap, Open-Meteo, &amp; Chart.js
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
