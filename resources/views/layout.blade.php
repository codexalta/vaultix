<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vaultix Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="text-slate-800" x-data="{ mobileMenuOpen: false }">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar (Desktop) -->
        <aside class="hidden lg:flex w-64 bg-slate-900 text-slate-300 flex-col shrink-0">
            <div class="p-6 text-white font-bold text-2xl tracking-tight flex items-center gap-2">
                <span class="w-8 h-8 bg-indigo-500 rounded-lg flex items-center justify-center text-xs text-white">V</span>
                Vaultix
            </div>
            <nav class="flex-1 px-4 space-y-2 mt-4">
                @include('vaultix::partials.nav-links')
            </nav>
            <div class="p-4 text-[10px] text-slate-500 text-center border-t border-slate-800">
                v1.0.0 Stable
            </div>
        </aside>

        <!-- Mobile Sidebar Overlay -->
        <div x-show="mobileMenuOpen" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-40 lg:hidden bg-slate-900/60 backdrop-blur-sm" 
             @click="mobileMenuOpen = false" x-cloak></div>

        <!-- Mobile Sidebar Content -->
        <aside x-show="mobileMenuOpen"
               x-transition:enter="transition ease-out duration-300 transform"
               x-transition:enter-start="-translate-x-full"
               x-transition:enter-end="translate-x-0"
               x-transition:leave="transition ease-in duration-200 transform"
               x-transition:leave-start="translate-x-0"
               x-transition:leave-end="-translate-x-full"
               class="fixed inset-y-0 left-0 z-50 w-72 bg-slate-900 text-slate-300 flex flex-col shadow-2xl lg:hidden" x-cloak>
            <div class="p-6 text-white font-bold text-2xl tracking-tight flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 bg-indigo-500 rounded-lg flex items-center justify-center text-xs">V</span>
                    Vaultix
                </div>
                <button @click="mobileMenuOpen = false" class="text-slate-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <nav class="flex-1 px-4 space-y-2 mt-4">
                @include('vaultix::partials.nav-links')
            </nav>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Header -->
            <header class="h-16 border-b bg-white flex items-center justify-between px-4 lg:px-8 shrink-0">
                <div class="flex items-center gap-4">
                    <button @click="mobileMenuOpen = true" class="lg:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <div class="text-sm text-slate-500 hidden sm:block">System Status: <span class="text-emerald-500 font-semibold">Active</span></div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest hidden md:block">Secure Session</span>
                    <div class="w-8 h-8 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-bold text-xs">
                        {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-4 lg:p-8 bg-slate-50/50">
                <div class="max-w-7xl mx-auto">
                    @if(session('success'))
                        <div class="mb-6 p-4 bg-emerald-50 text-emerald-700 rounded-2xl border border-emerald-100 flex items-center gap-3 shadow-sm animate-fade-in">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            <span class="text-sm font-medium">{{ session('success') }}</span>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-6 p-4 bg-rose-50 text-rose-700 rounded-2xl border border-rose-100 flex items-center gap-3 shadow-sm animate-fade-in">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                            <span class="text-sm font-medium">{{ session('error') }}</span>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>
    </div>
</body>
</html>
