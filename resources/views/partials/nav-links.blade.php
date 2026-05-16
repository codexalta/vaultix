<a href="{{ route('vaultix.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('vaultix.index') ? 'bg-slate-800 text-white shadow-lg shadow-indigo-500/10' : 'hover:bg-slate-800 transition' }}">
    <svg class="w-5 h-5 {{ request()->routeIs('vaultix.index') ? 'text-indigo-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
    <span class="font-semibold tracking-wide">Dashboard</span>
</a>
<a href="{{ route('vaultix.destinations.create') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('vaultix.destinations.create') ? 'bg-slate-800 text-white shadow-lg shadow-indigo-500/10' : 'hover:bg-slate-800 transition' }}">
    <svg class="w-5 h-5 {{ request()->routeIs('vaultix.destinations.create') ? 'text-emerald-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <span class="font-semibold tracking-wide">Add Storage</span>
</a>
<a href="{{ route('vaultix.settings') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('vaultix.settings') ? 'bg-slate-800 text-white shadow-lg shadow-indigo-500/10' : 'hover:bg-slate-800 transition' }}">
    <svg class="w-5 h-5 {{ request()->routeIs('vaultix.settings') ? 'text-amber-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
    <span class="font-semibold tracking-wide">Settings</span>
</a>

@if(config('vaultix.super_admin') && auth()->user()->email === config('vaultix.super_admin'))
<a href="{{ route('vaultix.activities') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('vaultix.activities') ? 'bg-slate-800 text-white shadow-lg shadow-indigo-500/10' : 'hover:bg-slate-800 transition' }}">
    <svg class="w-5 h-5 {{ request()->routeIs('vaultix.activities') ? 'text-indigo-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
    <span class="font-semibold tracking-wide">Activity Logs</span>
</a>
@endif
