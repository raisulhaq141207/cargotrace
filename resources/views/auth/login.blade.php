@extends('layouts.app')

@section('title', 'Sign In')

@section('content')
<div class="min-h-[75vh] flex flex-col justify-center items-center py-10 px-4">
    <div class="max-w-md w-full bg-white p-8 rounded-2xl shadow-xl border border-slate-200">
        
        <!-- Header -->
        <div class="text-center mb-6">
            <div class="inline-flex w-14 h-14 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 items-center justify-center text-white text-2xl shadow-lg shadow-blue-500/30 mb-3">
                <i class="fa-solid fa-ship"></i>
            </div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Sign in to CargoTrace</h2>
            <p class="text-sm text-slate-500 mt-1">Global Shipment Visibility & Tracking Platform</p>
        </div>

        <!-- Form -->
        <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Email Address</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                        <i class="fa-solid fa-envelope text-sm"></i>
                    </span>
                    <input id="email" type="email" name="email" value="{{ old('email', 'admin@cargotrace.com') }}" required autofocus
                        class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm transition">
                </div>
                @error('email')
                <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Password</label>
                </div>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                        <i class="fa-solid fa-lock text-sm"></i>
                    </span>
                    <input id="password" type="password" name="password" value="password" required
                        class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm transition">
                </div>
                @error('password')
                <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center text-xs text-slate-600 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 mr-2">
                    Remember this device
                </label>
            </div>

            <button type="submit"
                class="w-full py-2.5 px-4 rounded-xl text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-500/20 transition duration-150 flex items-center justify-center gap-2">
                <span>Sign In to Dashboard</span>
                <i class="fa-solid fa-arrow-right text-xs"></i>
            </button>
        </form>

        <!-- Quick Demo Credentials for Lecturer / Recruiter -->
        <div class="mt-6 pt-5 border-t border-slate-100">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 text-center mb-2">
                <i class="fa-solid fa-key mr-1"></i> Quick Demo Accounts (Click to Fill)
            </div>
            <div class="grid grid-cols-3 gap-2">
                <button type="button" onclick="fillCreds('admin@cargotrace.com', 'password')"
                    class="p-2 rounded-lg border border-slate-200 hover:border-amber-400 hover:bg-amber-50/50 text-left transition group">
                    <div class="text-[11px] font-bold text-slate-800 group-hover:text-amber-700">Admin</div>
                    <div class="text-[9px] text-slate-400 truncate">admin@cargotrace.com</div>
                </button>
                <button type="button" onclick="fillCreds('operator@cargotrace.com', 'password')"
                    class="p-2 rounded-lg border border-slate-200 hover:border-blue-400 hover:bg-blue-50/50 text-left transition group">
                    <div class="text-[11px] font-bold text-slate-800 group-hover:text-blue-700">Operator</div>
                    <div class="text-[9px] text-slate-400 truncate">operator@cargotrace.com</div>
                </button>
                <button type="button" onclick="fillCreds('customer@cargotrace.com', 'password')"
                    class="p-2 rounded-lg border border-slate-200 hover:border-emerald-400 hover:bg-emerald-50/50 text-left transition group">
                    <div class="text-[11px] font-bold text-slate-800 group-hover:text-emerald-700">Customer</div>
                    <div class="text-[9px] text-slate-400 truncate">customer@cargotrace.com</div>
                </button>
            </div>
            <div class="text-[10px] text-center text-slate-400 mt-2">Password for all seed accounts: <code class="text-slate-600 bg-slate-100 px-1 py-0.5 rounded">password</code></div>
        </div>

        <!-- Footer link -->
        <div class="text-center mt-6 text-xs text-slate-500">
            Don't have an account? 
            <a href="{{ route('register') }}" class="font-semibold text-blue-600 hover:underline">Register customer account</a>
        </div>
    </div>
</div>

<script>
    function fillCreds(email, password) {
        document.getElementById('email').value = email;
        document.getElementById('password').value = password;
    }
</script>
@endsection

