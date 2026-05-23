@extends('vaultix::layout')

@section('content')
<div class="max-w-4xl mx-auto space-y-8">
    <div class="flex items-center gap-4">
        <a href="{{ route('vaultix.index') }}" class="p-2 rounded-full hover:bg-slate-100 transition text-slate-400">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </a>
        <h1 class="text-3xl font-bold text-slate-900">Settings & Access Control</h1>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Access Management -->
        <div class="bg-white rounded-2xl border shadow-sm p-8 space-y-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
                <div>
                    <h2 class="font-bold text-lg">Authorized Users</h2>
                    <p class="text-xs text-slate-400">Emails allowed to access this dashboard.</p>
                </div>
            </div>

            <form action="{{ route('vaultix.settings.emails') }}" method="POST" class="flex gap-2">
                @csrf
                <input type="email" name="email" placeholder="user@example.com" required
                       class="flex-1 px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none transition">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">Add</button>
            </form>

            <div class="space-y-2">
                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-100">
                    <span class="text-sm font-medium text-slate-700">{{ config('vaultix.super_admin') }}</span>
                    <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-700 text-[10px] font-bold uppercase">Super Admin</span>
                </div>
                @foreach($authorizedEmails as $email)
                <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-slate-100 group">
                    <span class="text-sm text-slate-600">{{ $email }}</span>
                    <form action="{{ route('vaultix.settings.emails.remove') }}" method="POST" onsubmit="return confirm('Remove this user?')">
                        @csrf @method('DELETE')
                        <input type="hidden" name="email" value="{{ $email }}">
                        <button type="submit" class="text-slate-300 hover:text-rose-500 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v2m3 4h.01"></path></svg>
                        </button>
                    </form>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Server Health & Alerts -->
        <div class="bg-white rounded-2xl border shadow-sm p-8 space-y-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                </div>
                <div>
                    <h2 class="font-bold text-lg">Server Health</h2>
                    <p class="text-xs text-slate-400">Disk monitoring and safety alerts.</p>
                </div>
            </div>

            <div class="p-4 rounded-xl {{ $diskUsage['is_low'] ? 'bg-rose-50 border-rose-100' : 'bg-slate-50 border-slate-100' }} border">
                <div class="flex justify-between text-xs font-semibold text-slate-500 mb-2">
                    <span>DISK USAGE</span>
                    <span>{{ $diskUsage['percentage'] }}%</span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                    <div class="h-full {{ $diskUsage['is_low'] ? 'bg-rose-500' : 'bg-emerald-500' }}" style="width: {{ $diskUsage['percentage'] }}%"></div>
                </div>
                <div class="mt-3 flex justify-between items-end">
                    <div>
                        <p class="text-xl font-bold text-slate-900">{{ $diskUsage['free'] }}</p>
                        <p class="text-[10px] text-slate-400 uppercase">Available Space</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-slate-600">{{ $diskUsage['total'] }}</p>
                        <p class="text-[10px] text-slate-400 uppercase">Total Capacity</p>
                    </div>
                </div>
            </div>

            <form action="{{ route('vaultix.settings.threshold') }}" method="POST" class="space-y-4 pt-4 border-t">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Low Space Alert Threshold (MB)</label>
                    <div class="flex gap-2">
                        <input type="number" name="threshold" value="{{ $threshold }}" min="100" required
                               class="flex-1 px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none transition">
                        <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg text-sm font-semibold hover:bg-slate-900 transition">Update</button>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-2 italic">Current free space: {{ round($diskUsage['free_mb'], 0) }} MB</p>
                </div>
            </form>

            <form action="{{ route('vaultix.settings.timezone') }}" method="POST" class="space-y-4 pt-4 border-t">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">System Timezone</label>
                    <div class="flex gap-2">
                        <select name="timezone" class="flex-1 px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none transition text-sm">
                            @foreach(timezone_identifiers_list() as $tz)
                                <option value="{{ $tz }}" {{ $timezone == $tz ? 'selected' : '' }}>{{ $tz }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">Save</button>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-2">Current Time: {{ now()->format('Y-m-d H:i:s') }}</p>
                </div>
            </form>

            @if($isSuperAdmin)
            <form action="{{ route('vaultix.settings.log_retention') }}" method="POST" class="space-y-4 pt-4 border-t">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Activity Log Retention (Days)</label>
                    <div class="flex gap-2">
                        <input type="number" name="days" value="{{ $logRetentionDays }}" min="1" required
                               class="flex-1 px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none transition">
                        <button type="submit" class="px-4 py-2 bg-slate-900 text-white rounded-lg text-sm font-semibold hover:bg-slate-800 transition">Save</button>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-2 italic">Logs older than {{ $logRetentionDays }} days will be auto-deleted.</p>
                </div>
            </form>
            @else
            <div class="space-y-2 pt-4 border-t">
                <label class="block text-sm font-semibold text-slate-700">Activity Log Retention (Days)</label>
                <div class="flex items-center gap-3 px-4 py-3 bg-slate-50 rounded-lg border border-slate-100">
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    <span class="text-sm text-slate-500">Retention period: <strong class="text-slate-700">{{ $logRetentionDays }} days</strong> &mdash; only Super Admin can change this setting.</span>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

    <!-- Activity Log Visibility -->
    @if($isSuperAdmin)
    <div class="bg-white rounded-2xl border shadow-sm p-8 space-y-6 mt-8">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-violet-100 text-violet-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
            </div>
            <div>
                <h2 class="font-bold text-lg">Activity Log Access</h2>
                <p class="text-xs text-slate-400">Control who can view the activity log.</p>
            </div>
        </div>

        <form action="{{ route('vaultix.settings.activity_visibility') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="vis-label flex items-start gap-3 p-4 border-2 rounded-xl cursor-pointer transition-all {{ $activityLogVisibility === 'super_admin_only' ? 'border-violet-500 bg-violet-50' : 'border-slate-200' }}">
                    <input type="radio" name="visibility" value="super_admin_only" class="mt-0.5" {{ $activityLogVisibility === 'super_admin_only' ? 'checked' : '' }}
                           onchange="this.form.submit()">
                    <div>
                        <span class="block text-sm font-bold text-slate-800">Super Admin Only</span>
                        <span class="block text-xs text-slate-500 mt-1">Only the Super Admin ({{ config('vaultix.super_admin') }}) can view the Activity Log.</span>
                    </div>
                </label>
                <label class="vis-label flex items-start gap-3 p-4 border-2 rounded-xl cursor-pointer transition-all {{ $activityLogVisibility === 'all_authorized' ? 'border-violet-500 bg-violet-50' : 'border-slate-200' }}">
                    <input type="radio" name="visibility" value="all_authorized" class="mt-0.5" {{ $activityLogVisibility === 'all_authorized' ? 'checked' : '' }}
                           onchange="this.form.submit()">
                    <div>
                        <span class="block text-sm font-bold text-slate-800">All Authorized Users</span>
                        <span class="block text-xs text-slate-500 mt-1">All users in the authorized email list can view the Activity Log.</span>
                    </div>
                </label>
            </div>
            <p class="text-[10px] text-slate-400 italic">
                Current setting: <strong>{{ $activityLogVisibility === 'super_admin_only' ? 'Super Admin Only' : 'All Authorized Users' }}</strong>
            </p>
        </form>
    </div>
    @endif

@endsection
