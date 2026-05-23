@extends('vaultix::layout')

@section('content')
<div class="space-y-8">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Disk Usage Status -->
        <div class="p-6 rounded-2xl border bg-white shadow-sm flex items-center justify-between {{ $diskUsage['is_low'] ? 'ring-2 ring-rose-500 bg-rose-50' : '' }}">
            <div class="flex-1">
                <h3 class="text-slate-500 text-sm font-medium">Server Disk Space</h3>
                <p class="text-xl font-bold mt-1 text-slate-900">{{ $diskUsage['free'] }} <span class="text-xs font-normal text-slate-400">Free</span></p>
                <div class="mt-3 w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                    <div class="h-full {{ $diskUsage['is_low'] ? 'bg-rose-500' : 'bg-indigo-500' }}" style="width: {{ $diskUsage['percentage'] }}%"></div>
                </div>
            </div>
            <div class="ml-4 w-10 h-10 rounded-full {{ $diskUsage['is_low'] ? 'bg-rose-100 text-rose-500' : 'bg-indigo-100 text-indigo-500' }} flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            </div>
        </div>

        <!-- Backup Payload -->
        <div class="p-6 rounded-2xl border bg-white shadow-sm flex items-center justify-between">
            <div class="flex-1">
                <h3 class="text-slate-500 text-sm font-medium">Base Project Size</h3>
                <p class="text-xl font-bold mt-1 text-slate-900">{{ $projectSize['formatted'] }}</p>
                <p class="text-[10px] text-slate-400 mt-2 uppercase tracking-wider">DB: {{ round($projectSize['db']/1024/1024, 1) }}MB | Files: {{ round($projectSize['files']/1024/1024, 1) }}MB</p>
            </div>
            <div class="ml-4 w-10 h-10 rounded-full bg-emerald-100 text-emerald-500 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
            </div>
        </div>

        <!-- Scheduler Status -->
        <div class="p-6 rounded-2xl border bg-white shadow-sm flex items-center justify-between">
            <div>
                <h3 class="text-slate-500 text-sm font-medium">Scheduler</h3>
                <p class="text-xl font-bold mt-1 {{ $isSchedulerHealthy ? 'text-slate-900' : 'text-rose-500' }}">
                    {{ $isSchedulerHealthy ? 'Healthy' : 'Offline' }}
                </p>
                <p class="text-[10px] text-slate-400 mt-1 uppercase tracking-wider">Last Run: {{ $schedulerLastRun ? $schedulerLastRun->diffForHumans() : 'Never' }}</p>
            </div>
            <div class="ml-4 w-10 h-10 rounded-full {{ $isSchedulerHealthy ? 'bg-emerald-100 text-emerald-500' : 'bg-rose-100 text-rose-500' }} flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>

        <!-- Queue Status -->
        <div class="p-6 rounded-2xl border bg-white shadow-sm flex items-center justify-between">
            <div>
                <h3 class="text-slate-500 text-sm font-medium">Queue Worker</h3>
                <p class="text-xl font-bold mt-1 {{ $isQueueHealthy ? 'text-slate-900' : 'text-amber-500' }}">
                    {{ $isQueueHealthy ? 'Active' : 'Offline' }}
                </p>
                <p class="text-[10px] text-slate-400 mt-1 uppercase tracking-wider">Ready for jobs</p>
            </div>
            <div class="ml-4 w-10 h-10 rounded-full {{ $isQueueHealthy ? 'bg-indigo-100 text-indigo-500' : 'bg-amber-100 text-amber-500' }} flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            </div>
        </div>
    </div>

    @if($diskUsage['is_low'])
    <div class="p-4 rounded-xl bg-rose-50 border border-rose-100 flex items-center gap-3 text-rose-700 animate-pulse">
        <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
        <div class="text-sm">
            <p class="font-bold">CRITICAL: Low Disk Space Detected!</p>
            <p>Server has only <b>{{ $diskUsage['free'] }}</b> left. Backup jobs might fail or crash the server. Please clear some space immediately.</p>
        </div>
    </div>
    @endif

    <!-- Backup Jobs Table -->
    <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
        <div class="p-6 border-b flex items-center justify-between">
            <h2 class="font-bold text-lg">Configured Backups</h2>
            <div class="flex gap-2">
                <a href="{{ route('vaultix.export') }}" class="px-4 py-2 border border-slate-200 text-slate-600 rounded-lg text-sm font-semibold hover:bg-slate-50 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Export
                </a>
                <form id="importForm" action="{{ route('vaultix.import') }}" method="POST" enctype="multipart/form-data" class="hidden">
                    @csrf
                    <input type="file" name="config_file" id="configFile" onchange="document.getElementById('importForm').submit()">
                </form>
                <button onclick="document.getElementById('configFile').click()" class="px-4 py-2 border border-slate-200 text-slate-600 rounded-lg text-sm font-semibold hover:bg-slate-50 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    Import
                </button>
                <a href="{{ route('vaultix.settings') }}" class="px-4 py-2 border border-slate-200 text-slate-600 rounded-lg text-sm font-semibold hover:bg-slate-50 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                    Settings
                </a>
                <a href="{{ route('vaultix.destinations.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">Add Storage</a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left min-w-[800px]">
                <thead class="bg-slate-50 border-b">
                    <tr>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase">Job Name</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase">Provider</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase">Schedule</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase">Retention / Capacity</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase">Last Run</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase">Next Run</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($jobs as $job)
                    @php
                        $backupsPerDay = ($job->frequency === 'hourly') ? 24 : (($job->frequency === '6_hours') ? 4 : (($job->frequency === '12_hours') ? 2 : 1));
                        if($job->frequency === 'weekly') $backupsPerDay = 1/7;
                        if($job->frequency === 'monthly') $backupsPerDay = 1/30;

                        $estFiles = 0;
                        if($job->frequency === 'monthly') { 
                            $estFiles = $job->keep_monthly_backups_for_months ?: 1; 
                        }
                        else if($job->frequency === 'weekly') { 
                            $estFiles = max($job->keep_weekly_backups_for_weeks, $job->keep_monthly_backups_for_months); 
                        }
                        else {
                            $estFiles = ($job->keep_all_backups_for_days * $backupsPerDay);
                            if ($job->keep_daily_backups_for_days > $job->keep_all_backups_for_days) {
                                $estFiles += ($job->keep_daily_backups_for_days - $job->keep_all_backups_for_days);
                            }
                            if ($job->keep_weekly_backups_for_weeks * 7 > $job->keep_daily_backups_for_days) {
                                $estFiles += ($job->keep_weekly_backups_for_weeks - ($job->keep_daily_backups_for_days / 7));
                            }
                            if ($job->keep_monthly_backups_for_months * 30 > $job->keep_weekly_backups_for_weeks * 7) {
                                $estFiles += ($job->keep_monthly_backups_for_months - ($job->keep_weekly_backups_for_weeks * 7 / 30));
                            }
                        }
                        
                        $estTotalStorage = $estFiles * ($projectSize['total'] * 0.4);
                        $formattedProjection = round($estTotalStorage / 1024 / 1024 / 1024, 2) . ' GB';
                        if($estTotalStorage < 1024*1024*1024) $formattedProjection = round($estTotalStorage / 1024 / 1024, 0) . ' MB';
                    @endphp
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-4 font-medium text-slate-900">{{ $job->name }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-slate-100 text-slate-600 rounded text-xs font-medium uppercase">
                                {{ $job->destination->provider }}
                            </span>
                        </td>
                        @php
                            $freqLabels = ['hourly' => 'Hourly', '6_hours' => 'Every 6h', '12_hours' => 'Every 12h', 'daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'];
                            $freqColors = ['hourly' => 'bg-purple-100 text-purple-700', '6_hours' => 'bg-blue-100 text-blue-700', '12_hours' => 'bg-cyan-100 text-cyan-700', 'daily' => 'bg-indigo-100 text-indigo-700', 'weekly' => 'bg-emerald-100 text-emerald-700', 'monthly' => 'bg-amber-100 text-amber-700'];
                            $freqLabel = $freqLabels[$job->frequency] ?? ucfirst($job->frequency);
                            $freqColor = $freqColors[$job->frequency] ?? 'bg-slate-100 text-slate-600';
                            $schedTime = $job->backup_time && !in_array($job->frequency, ['hourly', '6_hours', '12_hours']) ? ' @ ' . $job->backup_time : '';
                            $schedDay  = in_array($job->frequency, ['weekly', 'monthly']) && $job->backup_day ? ' (' . ucfirst($job->backup_day) . ')' : '';
                        @endphp
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-[10px] font-bold {{ $freqColor }} uppercase">{{ $freqLabel }}</span>
                            @if($schedTime)
                                <span class="block text-[10px] text-slate-400 mt-0.5">{{ $schedTime }}{{ $schedDay }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold text-slate-700">~{{ ceil($estFiles) }} Files</span>
                                <span class="text-xs text-slate-400">/</span>
                                <span class="text-xs font-semibold text-indigo-600">Est. {{ $formattedProjection }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($job->last_run_at)
                                <span class="block text-sm font-medium text-slate-700">{{ \Carbon\Carbon::parse($job->last_run_at)->format('M d, Y') }}</span>
                                <span class="block text-[10px] text-slate-400">{{ \Carbon\Carbon::parse($job->last_run_at)->diffForHumans() }}</span>
                            @else
                                <span class="text-slate-400 italic text-sm">Never</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500">{{ $job->next_run_at ? \Carbon\Carbon::parse($job->next_run_at)->diffForHumans() : 'Pending' }}</td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <form action="{{ route('vaultix.destinations.test', $job->destination) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" title="Test Connection" class="p-2 bg-slate-50 text-slate-400 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg transition-all border border-transparent hover:border-indigo-100">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path></svg>
                                    </button>
                                </form>
                                <a href="{{ route('vaultix.destinations.edit', $job->destination) }}" title="Edit Job" class="p-2 bg-slate-50 text-slate-400 hover:bg-amber-50 hover:text-amber-600 rounded-lg transition-all border border-transparent hover:border-amber-100">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </a>
                                <form onsubmit="handleRunNow(event, this, '{{ route('vaultix.run', $job) }}')" class="inline">
                                    @csrf
                                    <button type="submit" title="Run Backup Now" class="run-now-btn px-3 py-1.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-all text-xs font-bold shadow-sm">
                                        Run Now
                                    </button>
                                </form>
                                <form action="{{ route('vaultix.destinations.destroy', $job->destination) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this job and destination?')" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" title="Delete Job" class="p-2 bg-slate-50 text-slate-400 hover:bg-rose-50 hover:text-rose-600 rounded-lg transition-all border border-transparent hover:border-rose-100">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Backup History Table -->
    <div class="bg-white rounded-2xl border shadow-sm overflow-hidden" x-data="{ showFilters: false }">
        <div class="p-6 border-b flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h2 class="font-bold text-lg text-slate-900">Recent Backup History</h2>
                @if(request()->anyFilled(['provider', 'start_date', 'end_date', 'status']))
                    <span class="flex h-2 w-2 rounded-full bg-indigo-500 animate-pulse"></span>
                @endif
            </div>
            
            <div class="flex items-center gap-3">
                <button @click="showFilters = true" class="px-4 py-2 bg-slate-50 border border-slate-200 text-slate-600 rounded-xl text-sm font-bold hover:bg-slate-100 transition flex items-center gap-2 shadow-sm group">
                    <svg class="w-4 h-4 text-slate-400 group-hover:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                    Filters
                    @php $activeFilters = count(array_filter(request()->only(['provider', 'start_date', 'end_date', 'status']))); @endphp
                    @if($activeFilters > 0)
                        <span class="bg-indigo-600 text-white text-[10px] px-1.5 py-0.5 rounded-full">{{ $activeFilters }}</span>
                    @endif
                </button>

                <div class="h-6 w-px bg-slate-100 mx-1"></div>

                @if(request()->has('all'))
                    <a href="{{ route('vaultix.index') }}" class="text-xs font-bold text-indigo-600 hover:underline">Paginated</a>
                @else
                    <a href="{{ route('vaultix.index', ['all' => 1]) }}" class="text-xs font-bold text-slate-400 hover:text-indigo-600 transition">Show All</a>
                @endif
            </div>
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
                    Filter Backups
                </h3>
                <button @click="showFilters = false" class="p-2 rounded-lg hover:bg-slate-200 transition text-slate-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form action="{{ route('vaultix.index') }}" method="GET" class="flex-1 overflow-y-auto p-6 space-y-8">
                <div class="space-y-6">
                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">Storage Provider</label>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach(['gdrive' => 'Google Drive', 's3' => 'AWS S3', 'r2' => 'Cloudflare R2', 'sftp' => 'SFTP'] as $val => $label)
                            <label class="cursor-pointer group">
                                <input type="radio" name="provider" value="{{ $val }}" class="sr-only peer" {{ request('provider') == $val ? 'checked' : '' }}>
                                <div class="p-3 border rounded-xl text-center text-xs font-semibold peer-checked:bg-indigo-50 peer-checked:border-indigo-500 peer-checked:text-indigo-600 hover:border-indigo-200 transition">
                                    {{ $label }}
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">Backup Status</label>
                        <div class="flex gap-2">
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="status" value="success" class="sr-only peer" {{ request('status') == 'success' ? 'checked' : '' }}>
                                <div class="py-2 border rounded-xl text-center text-xs font-semibold peer-checked:bg-emerald-50 peer-checked:border-emerald-500 peer-checked:text-emerald-600 transition">Success</div>
                            </label>
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="status" value="failed" class="sr-only peer" {{ request('status') == 'failed' ? 'checked' : '' }}>
                                <div class="py-2 border rounded-xl text-center text-xs font-semibold peer-checked:bg-rose-50 peer-checked:border-rose-500 peer-checked:text-rose-600 transition">Failed</div>
                            </label>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">Date Range</label>
                        <div class="space-y-2">
                            <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium outline-none focus:ring-2 focus:ring-indigo-500">
                            <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

                <div class="pt-6 border-t flex flex-col gap-3">
                    <button type="submit" class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-bold text-sm hover:bg-indigo-700 shadow-xl shadow-indigo-100 transition">Apply Filters</button>
                    <a href="{{ route('vaultix.index') }}" class="w-full py-3 text-center text-slate-400 font-bold text-xs hover:text-rose-500 transition">Reset All Filters</a>
                </div>
            </form>
        </div>

        @if(request()->anyFilled(['provider', 'start_date', 'end_date', 'status']))
            <div class="px-6 py-3 bg-indigo-50 border-b flex items-center justify-between animate-fade-in">
                <div class="flex items-center gap-2 text-indigo-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p class="text-xs font-semibold">Found <b>{{ $backups->total() }}</b> records matching your criteria.</p>
                </div>
                <a href="{{ route('vaultix.index') }}" class="text-[10px] font-bold uppercase tracking-widest text-indigo-400 hover:text-indigo-600 transition">Clear Filters</a>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full text-left min-w-[900px]">
                <thead class="bg-slate-50 border-b">
                    <tr>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase">Date & Time</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase">Destination</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase">Schedule</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase">File Name</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase">Size</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase">Status</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($backups as $backup)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-4">
                            <span class="text-slate-900 font-medium block">{{ $backup->completed_at ? $backup->completed_at->format('M d, Y') : 'N/A' }}</span>
                            <span class="text-[10px] text-slate-400 block">{{ $backup->completed_at ? $backup->completed_at->format('H:i:s') : '' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-slate-600 block">{{ $backup->job->name ?? 'Unknown' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $bFreqLabels = ['hourly' => 'Hourly', '6_hours' => 'Every 6h', '12_hours' => 'Every 12h', 'daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'];
                                $bFreqColors = ['hourly' => 'bg-purple-100 text-purple-700', '6_hours' => 'bg-blue-100 text-blue-700', '12_hours' => 'bg-cyan-100 text-cyan-700', 'daily' => 'bg-indigo-100 text-indigo-700', 'weekly' => 'bg-emerald-100 text-emerald-700', 'monthly' => 'bg-amber-100 text-amber-700'];
                                $bFreq = $backup->job->frequency ?? null;
                                $bFreqLabel = $bFreq ? ($bFreqLabels[$bFreq] ?? ucfirst($bFreq)) : '—';
                                $bFreqColor = $bFreq ? ($bFreqColors[$bFreq] ?? 'bg-slate-100 text-slate-600') : 'bg-slate-100 text-slate-400';
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $bFreqColor }} uppercase">{{ $bFreqLabel }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500 truncate max-w-[150px]">
                            {{ $backup->file_name }}
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500">{{ $backup->human_size }}</td>
                        <td class="px-6 py-4">
                            @if($backup->status === 'success')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 uppercase">Success</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700 uppercase">Failed</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                @if($backup->status === 'success')
                                    <a href="{{ URL::temporarySignedRoute('vaultix.backups.download', now()->addMinutes(5), ['backup' => $backup]) }}" onclick="handleDownloadClick(this)" title="Download Backup" class="download-btn p-2 bg-slate-50 text-indigo-600 hover:bg-indigo-600 hover:text-white rounded-lg transition-all border border-transparent hover:border-indigo-100">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                    </a>
                                @endif
                                <form action="{{ route('vaultix.backups.destroy', $backup) }}" method="POST" onsubmit="return handleSafeDelete(this)" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" title="Delete Permanent" class="delete-btn p-2 bg-slate-50 text-slate-400 hover:bg-rose-600 hover:text-white rounded-lg transition-all border border-transparent hover:border-rose-100">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($backups->hasPages())
            <div class="p-6 bg-slate-50 border-t">
                {{ $backups->links() }}
            </div>
        @endif
    </div>
</div>

<script>
    let currentLatestId = {{ $backups->first()?->id ?? 0 }};
    let pollingInterval = null;

    function startPollingForBackups() {
        if (pollingInterval) return;
        pollingInterval = setInterval(() => {
            fetch('{{ route('vaultix.backups.latest-id') }}')
                .then(response => response.json())
                .then(data => {
                    if (data.id > currentLatestId) {
                        clearInterval(pollingInterval);
                        window.location.reload();
                    }
                })
                .catch(err => console.error('Polling error:', err));
        }, 5000);
    }

    function handleRunNow(event, form, url) {
        event.preventDefault();
        const btn = form.querySelector('.run-now-btn');
        const originalText = btn.innerText;
        
        btn.innerText = 'Checking...';
        btn.classList.add('opacity-50', 'pointer-events-none');

        fetch(url, {
            method: 'POST',
            headers: { 
                'X-CSRF-TOKEN': '{{ csrf_token() }}', 
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(async response => {
            const data = await response.json();
            
            if (!response.ok) {
                // Show specific error message (e.g., Insufficient Storage)
                alert(data.error || 'Failed to start backup.');
                btn.innerText = originalText;
                btn.classList.remove('opacity-50', 'pointer-events-none');
                return;
            }

            // Success: dispatched
            btn.innerText = 'Backing up...';
            startPollingForBackups();
        })
        .catch(err => {
            console.error('Run Now failed:', err);
            btn.innerText = originalText;
            btn.classList.remove('opacity-50', 'pointer-events-none');
            alert('A technical error occurred.');
        });
    }

    function handleDownloadClick(btn) {
        const originalHTML = btn.innerHTML;
        btn.innerHTML = `<svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>`;
        btn.classList.add('opacity-75', 'pointer-events-none');
        
        // Revert after 5 seconds (assuming download started)
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.classList.remove('opacity-75', 'pointer-events-none');
        }, 5000);
    }

    function handleSafeDelete(form) {
        if (!confirm('Are you sure?')) return false;
        const btn = form.querySelector('.delete-btn');
        btn.innerText = 'Deleting...';
        btn.classList.add('opacity-50', 'pointer-events-none');
        return true;
    }
</script>
@endsection
