@extends('vaultix::layout')

@section('content')
<div class="space-y-6" x-data="{ showFilters: false }">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Activity Logs</h1>
            <p class="text-slate-500 text-sm">Monitor all administrative actions and security events.</p>
        </div>
        <div class="flex items-center gap-3">
            <!-- Export Dropdown -->
            <div class="relative" x-data="{ exportOpen: false }">
                <button @click="exportOpen = !exportOpen" class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 hover:bg-slate-50 transition shadow-sm">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Export
                    <svg class="w-3 h-3 text-slate-400 transition-transform" :class="exportOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="exportOpen" @click.away="exportOpen = false" class="absolute right-0 mt-2 w-40 bg-white rounded-2xl shadow-xl border border-slate-100 py-2 z-50 animate-in zoom-in-95 duration-100" x-cloak>
                    <a href="{{ route('vaultix.activities.export', array_merge(request()->all(), ['format' => 'csv'])) }}" class="flex items-center px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-indigo-600 transition font-medium">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 mr-3"></span>
                        Export as CSV
                    </a>
                    <a href="{{ route('vaultix.activities.export', array_merge(request()->all(), ['format' => 'json'])) }}" class="flex items-center px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-indigo-600 transition font-medium">
                        <span class="w-2 h-2 rounded-full bg-amber-400 mr-3"></span>
                        Export as JSON
                    </a>
                </div>
            </div>

            <button @click="showFilters = true" class="px-4 py-2 bg-slate-50 border border-slate-200 text-slate-600 rounded-xl text-sm font-bold hover:bg-slate-100 transition flex items-center gap-2 shadow-sm group">
                <svg class="w-4 h-4 text-slate-400 group-hover:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                Filters
                @php $activeFilters = count(array_filter(request()->only(['user', 'action', 'type']))); @endphp
                @if($activeFilters > 0)
                    <span class="bg-indigo-600 text-white text-[10px] px-1.5 py-0.5 rounded-full">{{ $activeFilters }}</span>
                @endif
            </button>
            <span class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-xs font-bold">Super Admin Only</span>
        </div>

        <!-- Filter Drawer Overlay -->
        <div x-show="showFilters" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 bg-slate-900/60 lg:hidden" x-cloak @click="showFilters = false"></div>

        <!-- Filter Drawer Content (Right Side) -->
        <div x-show="showFilters"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             class="fixed inset-y-0 right-0 z-50 w-full max-w-sm bg-white shadow-2xl flex flex-col border-l" x-cloak>
            
            <div class="p-6 border-b bg-slate-50 flex items-center justify-between">
                <h3 class="font-bold text-lg text-slate-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                    Filter Activities
                </h3>
                <button @click="showFilters = false" class="p-2 rounded-lg hover:bg-slate-200 transition text-slate-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form action="{{ route('vaultix.activities') }}" method="GET" class="flex-1 overflow-y-auto p-6 space-y-8">
                <div class="space-y-6">
                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">User Account</label>
                        <select name="user" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">All Users</option>
                            @foreach($users as $user)
                                <option value="{{ $user->user_email }}" {{ request('user') == $user->user_email ? 'selected' : '' }}>{{ $user->user_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">Action Type</label>
                        <select name="action" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">All Actions</option>
                            @foreach($actions as $action)
                                <option value="{{ $action->action }}" {{ request('action') == $action->action ? 'selected' : '' }}>{{ strtoupper(str_replace('_', ' ', $action->action)) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">Entity Type</label>
                        <select name="type" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">All Types</option>
                            @foreach($types as $type)
                                <option value="{{ $type->entity_type }}" {{ request('type') == $type->entity_type ? 'selected' : '' }}>{{ $type->entity_type }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="pt-6 border-t flex flex-col gap-3">
                    <button type="submit" class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-bold text-sm hover:bg-indigo-700 shadow-xl shadow-indigo-100 transition">Apply Filters</button>
                    <a href="{{ route('vaultix.activities') }}" class="w-full py-3 text-center text-slate-400 font-bold text-xs hover:text-rose-500 transition">Reset All Filters</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Activity Table -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Action</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Entity</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Details</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Connection</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($activities as $activity)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 text-xs font-bold uppercase">
                                    {{ substr($activity->user_name, 0, 2) }}
                                </div>
                                <div>
                                    <span class="text-sm font-semibold text-slate-900 block">{{ $activity->user_name }}</span>
                                    <span class="text-[10px] text-slate-400 block">{{ $activity->user_email }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $color = match($activity->action) {
                                    'create' => 'bg-emerald-100 text-emerald-700',
                                    'update' => 'bg-amber-100 text-amber-700',
                                    'delete' => 'bg-rose-100 text-rose-700',
                                    'download' => 'bg-indigo-100 text-indigo-700',
                                    'manual_run' => 'bg-purple-100 text-purple-700',
                                    default => 'bg-slate-100 text-slate-700'
                                };
                            @endphp
                            <span class="px-2 py-1 {{ $color }} rounded text-[10px] font-bold uppercase">
                                {{ str_replace('_', ' ', $activity->action) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">
                            <span class="font-medium">{{ $activity->entity_type }}</span>
                            @if($activity->entity_name)
                                <span class="text-slate-400 mx-1">•</span>
                                <span class="text-slate-500 italic text-xs">{{ $activity->entity_name }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500 max-w-xs">
                            <div class="flex flex-col gap-1" x-data="{ showModal: false }">
                                <span>{{ $activity->description }}</span>
                                @if($activity->old_data || $activity->new_data)
                                    <button @click="showModal = true" class="text-[10px] text-indigo-600 font-bold hover:underline w-fit flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        View Changes
                                    </button>

                                    <!-- Modal Backdrop -->
                                    <template x-teleport="body">
                                        <div x-show="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60" x-cloak>
                                            <div @click.away="showModal = false" class="bg-white rounded-3xl shadow-2xl w-full max-w-5xl max-h-[85vh] flex flex-col overflow-hidden animate-in zoom-in duration-200">
                                                <!-- Modal Header -->
                                                <div class="p-6 border-b flex items-center justify-between bg-slate-50">
                                                    <div>
                                                        <h3 class="text-lg font-bold text-slate-900">Activity Comparison</h3>
                                                        <p class="text-xs text-slate-500">{{ $activity->description }}</p>
                                                    </div>
                                                    <button @click="showModal = false" class="p-2 hover:bg-slate-200 rounded-full transition">
                                                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                    </button>
                                                </div>

                                                <!-- Modal Body -->
                                                <div class="p-8 overflow-y-auto bg-slate-50/30">
                                                    <div class="space-y-6" x-data="{ 
                                                        syncScroll(e) {
                                                            const other = e.target.id === 'scroll-old' ? document.getElementById('scroll-new') : document.getElementById('scroll-old');
                                                            if (other) other.scrollTop = e.target.scrollTop;
                                                        }
                                                    }">
                                                        @php
                                                            $oldJson = json_encode($activity->old_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                                                            $newJson = json_encode($activity->new_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                                                            
                                                            $oldLines = explode("\n", $oldJson);
                                                            $newLines = explode("\n", $newJson);
                                                        @endphp

                                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                                            <!-- Old State -->
                                                            <div class="space-y-3">
                                                                <h4 class="text-xs font-bold text-rose-600 uppercase tracking-widest">Previous State</h4>
                                                                <div id="scroll-old" @scroll="syncScroll" class="bg-slate-900 rounded-2xl p-6 shadow-inner overflow-auto max-h-[50vh] scrollbar-hide">
                                                                    <pre class="text-[11px] font-mono leading-relaxed">@foreach($oldLines as $line)@php 
                                                                            $isRemoved = !in_array($line, $newLines);
                                                                        @endphp<div class="{{ $isRemoved ? 'bg-rose-500/20 -mx-6 px-6 text-rose-300' : 'text-slate-400' }}">{{ $line }}</div>@endforeach</pre>
                                                                </div>
                                                            </div>

                                                            <!-- New State -->
                                                            <div class="space-y-3">
                                                                <h4 class="text-xs font-bold text-emerald-600 uppercase tracking-widest">New State</h4>
                                                                <div id="scroll-new" @scroll="syncScroll" class="bg-slate-900 rounded-2xl p-6 shadow-inner overflow-auto max-h-[50vh] scrollbar-hide">
                                                                    <pre class="text-[11px] font-mono leading-relaxed">@foreach($newLines as $line)@php 
                                                                            $isAdded = !in_array($line, $oldLines);
                                                                        @endphp<div class="{{ $isAdded ? 'bg-emerald-500/20 -mx-6 px-6 text-emerald-300' : 'text-slate-400' }}">{{ $line }}</div>@endforeach</pre>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Modal Footer -->
                                                <div class="p-6 border-t bg-white flex justify-end">
                                                    <button @click="showModal = false" class="px-6 py-2 bg-slate-900 text-white rounded-xl text-sm font-bold hover:bg-slate-800 transition">Close Preview</button>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col gap-1">
                                <span class="text-[10px] font-mono bg-slate-50 px-1 rounded text-slate-500 w-fit">{{ $activity->ip_address }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-sm text-slate-900 block">{{ $activity->created_at->diffForHumans() }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-400 italic">No activity recorded yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($activities->hasPages())
        <div class="px-6 py-4 bg-slate-50/50 border-t border-slate-100">
            {{ $activities->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
