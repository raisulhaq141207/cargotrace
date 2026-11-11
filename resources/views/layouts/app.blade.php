<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - CargoTrace Global Visibility Platform</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: {
                            800: '#0f2238',
                            900: '#0b192c',
                            950: '#071220',
                        },
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#0284c7',
                            600: '#0369a1',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Google Fonts & Font Awesome Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Leaflet.js Map CSS & JS -->
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
            font-family: inherit;
        }
    </style>
    @stack('styles')
</head>
<body class="h-full flex flex-col bg-[#f0f4f9] text-slate-800 antialiased">

    <!-- Top Dark Navy Navigation Bar (Matches Mockup) -->
    <header class="bg-[#0b192c] border-b border-slate-800/80 sticky top-0 z-50 shadow-md">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                <!-- Left: Brand Logo & Current Section Title -->
                <div class="flex items-center space-x-8">
                    <!-- Brand Logo -->
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 group">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-sky-500 to-blue-600 flex items-center justify-center text-white shadow-md shadow-sky-500/20 group-hover:scale-105 transition">
                            <i class="fa-solid fa-ship text-sm"></i>
                        </div>
                        <span class="text-lg font-bold tracking-tight text-white flex items-center">
                            Cargo<span class="text-sky-400">Trace</span>
                        </span>
                    </a>

                    <!-- Section Title (White) -->
                    <h2 class="hidden md:block text-lg font-bold text-white tracking-wide border-l border-slate-700/80 pl-6">
                        @yield('header_title', 'Dashboard')
                    </h2>
                </div>

                <!-- Right: Search, Live Indicator, Notifications & User Avatar -->
                <div class="flex items-center space-x-4">
                    
                    @auth
                    <!-- Search Input (Form) -->
                    <form method="GET" action="{{ route('dashboard') }}" class="hidden sm:block">
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <i class="fa-solid fa-magnifying-glass text-xs"></i>
                            </span>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..."
                                class="w-48 lg:w-64 pl-8 pr-3 py-1.5 rounded-xl bg-slate-800/80 border border-slate-700 text-xs text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                        </div>
                    </form>

                    <!-- Real-Time Pulse Badge -->
                    <div class="hidden lg:flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-[10px] font-bold uppercase tracking-wider">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        <span>Live</span>
                    </div>

                    <!-- Notification Bell -->
                    @php $unreadCount = auth()->user()->unreadNotifications()->count(); @endphp
                    <a href="{{ route('notifications.index') }}" class="relative p-2 text-slate-300 hover:text-white transition" title="Notifications">
                        <i class="fa-regular fa-bell text-base"></i>
                        <span id="nav-notif-badge" class="absolute top-1 right-1 flex h-4 w-4 {{ $unreadCount > 0 ? '' : 'hidden' }}">
                            <span class="relative inline-flex rounded-full h-4 w-4 bg-rose-500 text-[10px] font-bold text-white items-center justify-center">
                                <span id="nav-notif-count">{{ $unreadCount }}</span>
                            </span>
                        </span>
                    </a>

                    <!-- User Avatar Pill (SI Sarah Johnson style) -->
                    @php
                        $userNames = explode(' ', auth()->user()->name);
                        $initials = strtoupper(substr($userNames[0] ?? 'U', 0, 1) . substr($userNames[1] ?? '', 0, 1));
                        if(strlen($initials) === 1) $initials .= strtoupper(substr($userNames[0] ?? 'S', 1, 1));
                    @endphp
                    <div class="flex items-center space-x-2.5 pl-3 border-l border-slate-700/80">
                        <div class="w-8 h-8 rounded-full bg-sky-600/30 border border-sky-400/50 flex items-center justify-center text-xs font-bold text-sky-300">
                            {{ $initials }}
                        </div>
                        <div class="hidden sm:block text-left leading-tight">
                            <div class="text-xs font-semibold text-white flex items-center gap-1">
                                <span>{{ auth()->user()->name }}</span>
                                <i class="fa-solid fa-chevron-down text-[9px] text-slate-400 ml-1"></i>
                            </div>
                            <div class="text-[10px] text-slate-400 uppercase font-medium">{{ auth()->user()->role }}</div>
                        </div>
                    </div>
                    @else
                    <div class="flex items-center space-x-2">
                        <a href="{{ route('login') }}" class="px-3.5 py-1.5 text-xs font-medium text-slate-300 hover:text-white transition">Sign in</a>
                        <a href="{{ route('register') }}" class="px-3.5 py-1.5 text-xs font-medium text-white bg-sky-600 hover:bg-sky-500 rounded-lg shadow-sm transition">Register</a>
                    </div>
                    @endauth

                </div>

            </div>
        </div>
    </header>

    <!-- Main Container: Dark Sidebar + Light Content Area -->
    <div class="flex-1 flex overflow-hidden">
        
        @auth
        <!-- Left Sidebar Navigation (Dark Navy #0b192c) -->
        <aside class="w-56 lg:w-60 bg-[#0b192c] border-r border-slate-800/80 flex flex-col justify-between shrink-0 hidden md:flex">
            <!-- Navigation Links -->
            <div class="p-4 space-y-1.5">
                
                <!-- Dashboard -->
                <a href="{{ route('dashboard') }}"
                    class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('dashboard') ? 'bg-[#13354a] text-sky-400 shadow-sm' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                    <i class="fa-solid fa-house text-sm w-4 text-center {{ request()->routeIs('dashboard') ? 'text-sky-400' : 'text-slate-400' }}"></i>
                    <span>Dashboard</span>
                </a>

                <!-- Shipments -->
                <a href="{{ route('shipments.index') }}"
                    class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('shipments.*') ? 'bg-[#13354a] text-sky-400 shadow-sm' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                    <i class="fa-solid fa-box text-sm w-4 text-center {{ request()->routeIs('shipments.*') ? 'text-sky-400' : 'text-slate-400' }}"></i>
                    <span>Shipments</span>
                </a>

                <!-- Tracking -->
                <a href="{{ route('tracking.index') }}"
                    class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('tracking.*') ? 'bg-[#13354a] text-sky-400 shadow-sm' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                    <i class="fa-solid fa-location-dot text-sm w-4 text-center {{ request()->routeIs('tracking.*') ? 'text-sky-400' : 'text-slate-400' }}"></i>
                    <span>Tracking</span>
                </a>

                <!-- Exceptions -->
                <a href="{{ route('exceptions.index') }}"
                    class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('exceptions.*') ? 'bg-[#13354a] text-sky-400 shadow-sm' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                    <i class="fa-solid fa-triangle-exclamation text-sm w-4 text-center {{ request()->routeIs('exceptions.*') ? 'text-sky-400' : 'text-slate-400' }}"></i>
                    <span>Exceptions</span>
                </a>

                <!-- Carriers -->
                <a href="{{ route('carriers.index') }}"
                    class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('carriers.*') ? 'bg-[#13354a] text-sky-400 shadow-sm' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                    <i class="fa-solid fa-truck text-sm w-4 text-center {{ request()->routeIs('carriers.*') ? 'text-sky-400' : 'text-slate-400' }}"></i>
                    <span>Carriers</span>
                </a>

                <!-- Reports -->
                <a href="{{ route('reports.index') }}"
                    class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('reports.*') ? 'bg-[#13354a] text-sky-400 shadow-sm' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                    <i class="fa-solid fa-chart-line text-sm w-4 text-center {{ request()->routeIs('reports.*') ? 'text-sky-400' : 'text-slate-400' }}"></i>
                    <span>Reports</span>
                </a>

                <!-- Maritime News -->
                <a href="{{ route('news.index') }}"
                    class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('news.*') ? 'bg-[#13354a] text-sky-400 shadow-sm' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                    <i class="fa-solid fa-newspaper text-sm w-4 text-center {{ request()->routeIs('news.*') ? 'text-sky-400' : 'text-slate-400' }}"></i>
                    <span>Maritime News</span>
                </a>

                @if(auth()->user()->isAdmin())
                <!-- Audit Logs (Admin) -->
                <a href="{{ route('audit-logs.index') }}"
                    class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('audit-logs.*') ? 'bg-[#13354a] text-sky-400 shadow-sm' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                    <i class="fa-solid fa-shield-halved text-sm w-4 text-center {{ request()->routeIs('audit-logs.*') ? 'text-sky-400' : 'text-slate-400' }}"></i>
                    <span>Audit Logs</span>
                </a>
                @endif
            </div>

            <!-- Bottom: Logs Out Button -->
            <div class="p-4 border-t border-slate-800/80">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold text-slate-400 hover:text-rose-400 hover:bg-slate-800/60 transition">
                        <i class="fa-solid fa-circle-left text-sm w-4 text-center"></i>
                        <span>Logs Out</span>
                    </button>
                </form>
            </div>
        </aside>
        @endauth

        <!-- Main Content View Area (Light Gray #f0f4f9) -->
        <main class="flex-1 overflow-y-auto px-4 sm:px-6 lg:px-8 py-6">
            
            <!-- Flash Message Alerts -->
            @if(session('success'))
            <div class="mb-4 flex items-center p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 shadow-sm text-xs">
                <i class="fa-solid fa-circle-check text-emerald-500 text-base mr-2.5"></i>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
            @endif

            @if(session('error'))
            <div class="mb-4 flex items-center p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 shadow-sm text-xs">
                <i class="fa-solid fa-triangle-exclamation text-rose-500 text-base mr-2.5"></i>
                <span class="font-medium">{{ session('error') }}</span>
            </div>
            @endif

            @yield('content')
        </main>

    </div>

    <!-- Global Notification Polling -->
    @auth
    <script>
        (function() {
            async function syncNotifications() {
                try {
                    const res = await fetch('{{ route('realtime.dashboard-stats') }}', {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    const badge = document.getElementById('nav-notif-badge');
                    const count = document.getElementById('nav-notif-count');
                    if (badge && count) {
                        const val = data.unread_notifications || 0;
                        count.textContent = val;
                        if (val > 0) badge.classList.remove('hidden');
                        else badge.classList.add('hidden');
                    }
                } catch(e) {}
            }
            setInterval(syncNotifications, 10000);
        })();
    </script>
    @endauth

    @stack('scripts')
</body>
</html>
