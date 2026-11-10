<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CargoTrace') - Global Shipment Visibility Platform</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Font & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Leaflet.js Map CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .leaflet-container {
            z-index: 1;
        }
    </style>
    @stack('styles')
</head>
<body class="h-full flex flex-col text-slate-800 antialiased">

    <!-- Top Navigation Header -->
    <header class="bg-slate-900 border-b border-slate-800 sticky top-0 z-50 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand Logo -->
                <div class="flex items-center space-x-3">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white shadow-lg shadow-blue-500/30 group-hover:scale-105 transition duration-200">
                            <i class="fa-solid fa-ship text-lg"></i>
                        </div>
                        <div>
                            <span class="text-xl font-bold tracking-tight text-white flex items-center gap-1.5">
                                Cargo<span class="text-blue-400">Trace</span>
                            </span>
                            <span class="hidden sm:block text-[10px] tracking-widest text-slate-400 font-semibold uppercase">Visibility & Tracking</span>
                        </div>
                    </a>

                    <!-- Nav Links -->
                    @auth
                    <nav class="hidden md:flex ml-8 space-x-1">
                        <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-chart-pie mr-1.5 text-xs"></i> Dashboard
                        </a>
                        <a href="{{ route('shipments.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('shipments.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-box mr-1.5 text-xs"></i> Shipments
                        </a>
                        <a href="{{ route('carriers.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('carriers.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-truck mr-1.5 text-xs"></i> Carriers
                        </a>
                        <a href="{{ route('locations.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('locations.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-location-dot mr-1.5 text-xs"></i> Hubs & Ports
                        </a>
                        @if(auth()->user()->isAdmin())
                        <a href="{{ route('audit-logs.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('audit-logs.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-shield-halved mr-1.5 text-xs"></i> Audit Log
                        </a>
                        @endif
                    </nav>
                    @endauth
                </div>

                <!-- Right profile & actions -->
                @auth
                <div class="flex items-center space-x-4">
                    <!-- Notifications button -->
                    <a href="{{ route('notifications.index') }}" class="relative p-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800 transition">
                        <i class="fa-solid fa-bell text-base"></i>
                        @php $unreadCount = auth()->user()->unreadNotifications()->count(); @endphp
                        @if($unreadCount > 0)
                        <span class="absolute top-1 right-1 flex h-4 w-4">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-4 w-4 bg-red-500 text-[10px] font-bold text-white items-center justify-center">{{ $unreadCount }}</span>
                        </span>
                        @endif
                    </a>

                    <!-- User Pill -->
                    <div class="flex items-center space-x-3 pl-3 border-l border-slate-700">
                        <div class="text-right hidden sm:block">
                            <div class="text-sm font-semibold text-white leading-tight">{{ auth()->user()->name }}</div>
                            <div class="text-xs text-slate-400 flex items-center justify-end gap-1">
                                <span class="inline-block px-1.5 py-0.2 rounded text-[10px] font-bold uppercase {{ auth()->user()->isAdmin() ? 'bg-amber-500/20 text-amber-400' : (auth()->user()->isOperator() ? 'bg-blue-500/20 text-blue-400' : 'bg-emerald-500/20 text-emerald-400') }}">
                                    {{ auth()->user()->role }}
                                </span>
                            </div>
                        </div>

                        <!-- Logout Form -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" title="Logout" class="p-2 rounded-lg text-slate-400 hover:text-red-400 hover:bg-slate-800 transition">
                                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            </button>
                        </form>
                    </div>
                </div>
                @else
                <div class="flex items-center space-x-2">
                    <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-medium text-slate-300 hover:text-white transition">Sign in</a>
                    <a href="{{ route('register') }}" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-500 rounded-lg shadow-sm transition">Register</a>
                </div>
                @endauth
            </div>
        </div>
    </header>

    <!-- Flash message alerts -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full mt-4">
        @if(session('success'))
        <div class="mb-4 flex items-center p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 shadow-sm">
            <i class="fa-solid fa-circle-check text-emerald-500 text-lg mr-3"></i>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
        @endif

        @if(session('error'))
        <div class="mb-4 flex items-center p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 shadow-sm">
            <i class="fa-solid fa-triangle-exclamation text-rose-500 text-lg mr-3"></i>
            <span class="text-sm font-medium">{{ session('error') }}</span>
        </div>
        @endif

        @if(session('warning'))
        <div class="mb-4 flex items-center p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 shadow-sm">
            <i class="fa-solid fa-circle-exclamation text-amber-500 text-lg mr-3"></i>
            <span class="text-sm font-medium">{{ session('warning') }}</span>
        </div>
        @endif

        @if(session('info'))
        <div class="mb-4 flex items-center p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 shadow-sm">
            <i class="fa-solid fa-circle-info text-blue-500 text-lg mr-3"></i>
            <span class="text-sm font-medium">{{ session('info') }}</span>
        </div>
        @endif
    </div>

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 mt-auto py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-4">
            <div class="flex items-center space-x-2">
                <span class="font-bold text-slate-700">CargoTrace</span>
                <span>• Global Logistics Visibility Platform</span>
                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 font-medium">UAS Web Programming 2</span>
            </div>
            <div>
                <span>Group Scenario: Delay Threshold <strong>{{ config('cargotrace.delay_threshold_hours', 12) }}h</strong></span>
                <span class="mx-2">•</span>
                <span>Laravel 11 &amp; MySQL</span>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>

