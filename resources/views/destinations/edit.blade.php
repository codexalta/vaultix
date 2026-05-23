@extends('vaultix::layout')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Edit Backup Configuration</h1>
            <p class="text-slate-500">Update your storage credentials and schedule for <strong>{{ $destination->name }}</strong>.</p>
        </div>
    </div>

    <form action="{{ route('vaultix.destinations.update', $destination) }}" method="POST" class="space-y-6">
        @csrf @method('PUT')
        
        <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
            <div class="p-8 space-y-8">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Destination Name</label>
                    <input type="text" name="name" value="{{ old('name', $destination->name) }}" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none transition" required>
                </div>

                <!-- Provider Selection (Read-only for safety or allow change) -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-3">1. Storage Provider</label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @foreach(['gdrive' => 'Google Drive', 's3' => 'AWS S3', 'r2' => 'Cloudflare R2', 'sftp' => 'SFTP / Custom'] as $val => $label)
                        <label class="provider-label relative flex flex-col items-center p-4 border-2 rounded-2xl cursor-pointer transition-all {{ $destination->provider == $val ? 'selected-provider border-indigo-500 bg-indigo-50' : '' }}">
                            <input type="radio" name="provider" value="{{ $val }}" class="absolute opacity-0" {{ $destination->provider == $val ? 'checked' : '' }} onclick="updateSelection(this, 'provider-label')">
                            <span class="text-sm font-bold {{ $destination->provider == $val ? 'text-indigo-600' : 'text-slate-600' }}">{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <!-- Backup Type -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-3">2. What to backup?</label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach(['full' => 'Database & Files', 'db_only' => 'Database Only', 'files_only' => 'Files Only'] as $val => $label)
                        <label class="type-label flex items-center p-3 border-2 rounded-xl cursor-pointer hover:border-indigo-100 transition {{ old('backup_type', $job->type) == $val ? 'border-indigo-500 bg-indigo-50' : '' }}">
                            <input type="radio" name="backup_type" value="{{ $val }}" class="mr-3" {{ old('backup_type', $job->type) == $val ? 'checked' : '' }} onclick="updateSelection(this, 'type-label')">
                            <span class="text-sm font-medium">{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <!-- Frequency -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-3">3. Backup Frequency</label>
                    <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
                        @foreach(['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'hourly' => 'Hourly', '6_hours' => 'Every 6h', '12_hours' => 'Every 12h'] as $val => $label)
                        <label class="freq-label flex items-center p-3 border-2 rounded-xl cursor-pointer hover:border-indigo-100 transition {{ old('frequency', $job->frequency) == $val ? 'border-indigo-500 bg-indigo-50' : '' }}">
                            <input type="radio" name="frequency" value="{{ $val }}" class="mr-3" {{ old('frequency', $job->frequency) == $val ? 'checked' : '' }} onclick="updateSelection(this, 'freq-label')">
                            <span class="text-[10px] font-bold uppercase">{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <!-- Backup Time & Day -->
                <div id="scheduling-details" class="space-y-4 {{ in_array(old('frequency', $job->frequency), ['hourly']) ? 'hidden' : '' }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-semibold text-slate-700">Preferred Backup Time (24h format)</label>
                                <span class="text-[10px] font-bold px-2 py-1 bg-amber-100 text-amber-700 rounded-lg">Server Time: {{ now()->format('H:i') }}</span>
                            </div>
                            <input type="text" name="backup_time" value="{{ old('backup_time', $job->backup_time ?? '02:00') }}" placeholder="e.g. 02:00" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none transition">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Next Run Override (Optional)</label>
                            <input type="datetime-local" name="next_run_override" value="{{ old('next_run_override', $job->next_run_at ? \Carbon\Carbon::parse($job->next_run_at)->format('Y-m-d\TH:i') : '') }}" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none transition">
                            <p class="text-[10px] text-slate-400 mt-1">Leave empty to use automatic scheduling based on frequency.</p>
                        </div>

                        <!-- Weekly Day Selector -->
                        <div id="weekly-day-selector" class="{{ old('frequency', $job->frequency) == 'weekly' ? '' : 'hidden' }}">
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Backup Day of Week</label>
                            <select name="backup_day" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none transition">
                                @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                                <option value="{{ $day }}" {{ old('backup_day', $job->backup_day) == $day ? 'selected' : '' }}>{{ $day }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Monthly Date Selector -->
                        <div id="monthly-day-selector" class="{{ old('frequency', $job->frequency) == 'monthly' ? '' : 'hidden' }}">
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Backup Day of Month</label>
                            <select name="backup_day" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none transition">
                                @for($i=1; $i<=28; $i++)
                                <option value="{{ $i }}" {{ old('backup_day', $job->backup_day) == $i ? 'selected' : '' }}>{{ $i }}{{ in_array($i, [1,21,31]) ? 'st' : (in_array($i, [2,22]) ? 'nd' : (in_array($i, [3,23]) ? 'rd' : 'th')) }} of Month</option>
                                @endfor
                                <option value="last" {{ old('backup_day', $job->backup_day) == 'last' ? 'selected' : '' }}>Last Day of Month</option>
                            </select>
                        </div>
                    </div>
                    <p class="text-[10px] text-slate-400">For Hourly and 6h/12h intervals, this time acts as the <b>Starting Point</b>.</p>
                </div>

                <!-- Dynamic Credentials -->
                <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100">
                    @php
                        $flashToken = session('vaultix_gdrive_token');
                        $prefillRefreshToken = $flashToken['refresh_token'] ?? ($destination->credentials['refresh_token'] ?? '');
                        $prefillClientId     = $flashToken['client_id']     ?? ($destination->credentials['client_id']     ?? '');
                        $prefillClientSecret = $flashToken['client_secret'] ?? ($destination->credentials['client_secret'] ?? '');
                    @endphp
                    @if($flashToken)
                    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-start gap-3">
                        <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <div>
                            <p class="text-sm font-bold text-emerald-800">Google Drive authorized successfully!</p>
                            <p class="text-xs text-emerald-700 mt-1">The refresh token and credentials have been pre-filled below. Click <strong>Save Changes</strong> to confirm.</p>
                        </div>
                    </div>
                    @endif
                    <div id="fields-gdrive" class="provider-fields space-y-4 {{ old('provider', $destination->provider) == 'gdrive' ? '' : 'hidden' }}">
                        <!-- Google Drive Setup Guide -->
                        <div class="bg-indigo-50 border border-indigo-100 p-6 rounded-xl space-y-4 mb-6">
                            <div class="flex items-center gap-2 text-indigo-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <p class="font-bold uppercase tracking-wider text-sm">Google Drive Detailed Setup Guide</p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs text-indigo-600 leading-relaxed">
                                <div class="space-y-2">
                                    <p class="font-bold text-indigo-800">1. Google Cloud Console</p>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Redirect URI: <code class="bg-white px-1 py-0.5 rounded border border-indigo-200">{{ route('vaultix.auth.google.callback') }}</code></li>
                                        <li>Ensure <b>Google Drive API</b> is still enabled.</li>
                                    </ul>
                                </div>
                                <div class="space-y-2">
                                    <p class="font-bold text-indigo-800">2. Refresh Token</p>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>If backups fail, click <b>Update Token</b>.</li>
                                        <li>Make sure you use the same Client ID/Secret.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label class="block text-xs font-bold text-slate-400 uppercase mb-1">Client ID</label><input type="text" id="gdrive_client_id" name="credentials[client_id]" value="{{ old('credentials.client_id', $prefillClientId) }}" class="w-full px-4 py-2 rounded-lg border border-slate-200"></div>
                            <div><label class="block text-xs font-bold text-slate-400 uppercase mb-1">Client Secret</label><input type="password" id="gdrive_client_secret" name="credentials[client_secret]" value="{{ old('credentials.client_secret', $prefillClientSecret) }}" class="w-full px-4 py-2 rounded-lg border border-slate-200"></div>
                        </div>
                        <div class="flex items-end gap-3">
                            <div class="flex-1">
                                <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Refresh Token</label>
    <input type="text" name="credentials[refresh_token]" value="{{ old('credentials.refresh_token', $prefillRefreshToken) }}" class="w-full px-4 py-2 rounded-lg border border-slate-200 bg-slate-50{{ $flashToken ? ' ring-2 ring-emerald-400' : '' }}" id="edit_refresh_token">
                            </div>
                            <button type="button" onclick="generateGoogleToken()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-xs font-bold hover:bg-indigo-700 transition whitespace-nowrap mb-[1px] shadow-sm">Update Token</button>
                        </div>
                        <div><label class="block text-xs font-bold text-slate-400 uppercase mb-1">Folder ID (Optional)</label><input type="text" name="credentials[folder_id]" value="{{ old('credentials.folder_id', $destination->credentials['folder_id'] ?? '') }}" placeholder="1abc123..." class="w-full px-4 py-2 rounded-lg border border-slate-200"></div>
                    </div>

                    <div id="fields-s3" class="provider-fields space-y-4 {{ old('provider', $destination->provider) == 's3' ? '' : 'hidden' }}">
                        <div class="bg-amber-50 border border-amber-100 p-6 rounded-xl space-y-4 mb-6">
                            <div class="flex items-center gap-2 text-amber-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <p class="font-bold uppercase tracking-wider text-sm">AWS S3 Detailed Setup Guide</p>
                            </div>
                            <ul class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-2 text-xs text-amber-600 list-disc list-inside">
                                <li>Verify IAM User permissions (<b>AmazonS3FullAccess</b>).</li>
                                <li>Ensure the <b>Bucket Name</b> and <b>Region</b> are correct.</li>
                                <li>Region example: <code>us-east-1</code>.</li>
                            </ul>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label class="block text-xs font-bold text-slate-400 uppercase mb-1">Access Key</label><input type="text" name="credentials[key]" value="{{ old('credentials.key', $destination->credentials['key'] ?? '') }}" class="w-full px-4 py-2 rounded-lg border border-slate-200"></div>
                            <div><label class="block text-xs font-bold text-slate-400 uppercase mb-1">Secret Key</label><input type="password" name="credentials[secret]" value="{{ old('credentials.secret', $destination->credentials['secret'] ?? '') }}" class="w-full px-4 py-2 rounded-lg border border-slate-200"></div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label class="block text-xs font-bold text-slate-400 uppercase mb-1">Bucket</label><input type="text" name="credentials[bucket]" value="{{ old('credentials.bucket', $destination->credentials['bucket'] ?? '') }}" class="w-full px-4 py-2 rounded-lg border border-slate-200"></div>
                            <div><label class="block text-xs font-bold text-slate-400 uppercase mb-1">Region</label><input type="text" name="credentials[region]" value="{{ old('credentials.region', $destination->credentials['region'] ?? 'us-east-1') }}" class="w-full px-4 py-2 rounded-lg border border-slate-200"></div>
                        </div>
                    </div>

                    <div id="fields-r2" class="provider-fields space-y-4 {{ old('provider', $destination->provider) == 'r2' ? '' : 'hidden' }}">
                        <div class="bg-orange-50 border border-orange-100 p-6 rounded-xl space-y-4 mb-6">
                            <div class="flex items-center gap-2 text-orange-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <p class="font-bold uppercase tracking-wider text-sm">Cloudflare R2 Detailed Setup Guide</p>
                            </div>
                            <ul class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-2 text-xs text-orange-600 list-disc list-inside">
                                <li>Use the <b>S3 API Endpoint</b> from R2 dashboard.</li>
                                <li>API tokens must have <b>Object Read/Write</b> access.</li>
                            </ul>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label class="block text-xs font-bold text-slate-400 uppercase mb-1">Access Key</label><input type="text" name="credentials[r2_key]" value="{{ old('credentials.r2_key', $destination->credentials['r2_key'] ?? '') }}" class="w-full px-4 py-2 rounded-lg border border-slate-200"></div>
                            <div><label class="block text-xs font-bold text-slate-400 uppercase mb-1">Secret Key</label><input type="password" name="credentials[r2_secret]" value="{{ old('credentials.r2_secret', $destination->credentials['r2_secret'] ?? '') }}" class="w-full px-4 py-2 rounded-lg border border-slate-200"></div>
                        </div>
                        <div><label class="block text-xs font-bold text-slate-400 uppercase mb-1">Endpoint (URL)</label><input type="text" name="credentials[endpoint]" value="{{ old('credentials.endpoint', $destination->credentials['endpoint'] ?? '') }}" class="w-full px-4 py-2 rounded-lg border border-slate-200"></div>
                        <div><label class="block text-xs font-bold text-slate-400 uppercase mb-1">Bucket</label><input type="text" name="credentials[r2_bucket]" value="{{ old('credentials.r2_bucket', $destination->credentials['r2_bucket'] ?? '') }}" class="w-full px-4 py-2 rounded-lg border border-slate-200"></div>
                    </div>

                    <div id="fields-sftp" class="provider-fields space-y-4 {{ old('provider', $destination->provider) == 'sftp' ? '' : 'hidden' }}">
                        <div class="bg-slate-100 border border-slate-200 p-6 rounded-xl space-y-4 mb-6">
                            <div class="flex items-center gap-2 text-slate-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <p class="font-bold uppercase tracking-wider text-sm">SFTP Detailed Setup Guide</p>
                            </div>
                            <ul class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-2 text-xs text-slate-600 list-disc list-inside">
                                <li>Host can be an IP address or a domain name.</li>
                                <li>Ensure the user has write access to the path.</li>
                            </ul>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2"><label class="block text-xs font-bold text-slate-400 uppercase mb-1">Host</label><input type="text" name="credentials[host]" value="{{ old('credentials.host', $destination->credentials['host'] ?? '') }}" class="w-full px-4 py-2 rounded-lg border border-slate-200"></div>
                            <div><label class="block text-xs font-bold text-slate-400 uppercase mb-1">Port</label><input type="text" name="credentials[port]" value="{{ old('credentials.port', $destination->credentials['port'] ?? '22') }}" class="w-full px-4 py-2 rounded-lg border border-slate-200"></div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label class="block text-xs font-bold text-slate-400 uppercase mb-1">Username</label><input type="text" name="credentials[username]" value="{{ old('credentials.username', $destination->credentials['username'] ?? '') }}" class="w-full px-4 py-2 rounded-lg border border-slate-200"></div>
                            <div><label class="block text-xs font-bold text-slate-400 uppercase mb-1">Password</label><input type="password" name="credentials[password]" value="{{ old('credentials.password', $destination->credentials['password'] ?? '') }}" class="w-full px-4 py-2 rounded-lg border border-slate-200"></div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Root Directory (Optional)</label>
                            <input type="text" name="credentials[root]" value="{{ old('credentials.root', $destination->credentials['root'] ?? '/') }}" placeholder="e.g. /home/user/backups" class="w-full px-4 py-2 rounded-lg border border-slate-200">
                            <p class="mt-1 text-[10px] text-slate-400">Absolute path on the remote server where backups will be stored.</p>
                        </div>
                    </div>
                </div>

                <!-- Advanced Settings -->
                <div class="pt-8 border-t border-slate-100">
                    <label class="block text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        4. Retention Policy (Auto-Cleanup)
                    </label>
                    
                    <div class="bg-indigo-50 border border-indigo-100 p-4 rounded-xl mb-6 flex items-center gap-3">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <p class="text-xs text-indigo-700 font-medium" id="retention-summary-text">
                            Total Backup Coverage: <b>1 Year (365 days)</b>
                        </p>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 bg-slate-50 p-6 rounded-2xl border border-slate-100 mb-8">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Keep All For (Days)</label>
                            <input type="number" name="keep_all_backups_for_days" oninput="calculateRetention()" value="{{ old('keep_all_backups_for_days', $job->keep_all_backups_for_days) }}" class="retention-input w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none transition">
                            <p class="mt-1 text-[9px] text-slate-400">Every single backup file.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Daily (Days)</label>
                            <input type="number" name="keep_daily_backups_for_days" oninput="calculateRetention()" value="{{ old('keep_daily_backups_for_days', $job->keep_daily_backups_for_days) }}" class="retention-input w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none transition">
                            <p class="mt-1 text-[9px] text-slate-400">One backup per day.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Weekly (Weeks)</label>
                            <input type="number" name="keep_weekly_backups_for_weeks" oninput="calculateRetention()" value="{{ old('keep_weekly_backups_for_weeks', $job->keep_weekly_backups_for_weeks) }}" class="retention-input w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none transition">
                            <p class="mt-1 text-[9px] text-slate-400">One backup per week.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Monthly (Months)</label>
                            <input type="number" name="keep_monthly_backups_for_months" oninput="calculateRetention()" value="{{ old('keep_monthly_backups_for_months', $job->keep_monthly_backups_for_months) }}" class="retention-input w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none transition">
                            <p class="mt-1 text-[9px] text-slate-400">One backup per month.</p>
                        </div>
                    </div>

                    <label class="block text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        5. Advanced Backup Settings
                    </label>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50 p-6 rounded-2xl border border-slate-100">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Cloud Folder Name</label>
                            <input type="text" name="custom_folder_name" value="{{ old('custom_folder_name', $job->custom_folder_name) }}" placeholder="e.g. conference-backups" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none transition">
                            <p class="mt-1 text-[10px] text-slate-400">Leave empty to use project name (slugified). This will be created inside your Folder ID.</p>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Notification Email</label>
                            <input type="email" name="notification_email" value="{{ old('notification_email', $job->notification_email) }}" placeholder="admin@example.com" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none transition">
                            <p class="mt-1 text-[10px] text-slate-400">Where to send success/failure alerts.</p>
                        </div>

                        <div class="md:col-span-2 flex items-center gap-8 pt-2">
                            <label class="flex items-center cursor-pointer group">
                                <div class="relative">
                                    <input type="checkbox" name="notify_on_success" value="1" class="sr-only peer" {{ old('notify_on_success', $job->notify_on_success) ? 'checked' : '' }}>
                                    <div class="w-10 h-6 bg-slate-200 rounded-full peer peer-checked:bg-indigo-600 transition-all shadow-inner"></div>
                                    <div class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-all transform peer-checked:translate-x-4 shadow-sm"></div>
                                </div>
                                <div class="ml-3 text-sm font-medium text-slate-600">Notify on Success</div>
                            </label>

                            <label class="flex items-center cursor-pointer group">
                                <div class="relative">
                                    <input type="checkbox" name="notify_on_failure" value="1" class="sr-only peer" {{ old('notify_on_failure', $job->notify_on_failure) ? 'checked' : '' }}>
                                    <div class="w-10 h-6 bg-slate-200 rounded-full peer peer-checked:bg-rose-600 transition-all shadow-inner"></div>
                                    <div class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-all transform peer-checked:translate-x-4 shadow-sm"></div>
                                </div>
                                <div class="ml-3 text-sm font-medium text-slate-600">Notify on Failure</div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="p-8 bg-slate-50 border-t flex justify-between items-center">
                <button type="button" onclick="if(confirm('Are you sure you want to delete this configuration and all its backup history?')) document.getElementById('delete-config-form').submit()" class="px-6 py-3 bg-rose-50 text-rose-600 rounded-xl font-bold text-sm hover:bg-rose-600 hover:text-white transition shadow-sm">
                    Delete Configuration
                </button>
                <div class="flex gap-3">
                    <a href="{{ route('vaultix.index') }}" class="px-6 py-3 text-slate-600 font-semibold text-sm">Cancel</a>
                    <button type="submit" class="px-8 py-3 bg-indigo-600 text-white rounded-xl font-bold text-sm hover:bg-indigo-700 shadow-lg transition">Update Configuration</button>
                </div>
            </div>
        </div>
    </form>

    <form id="delete-config-form" action="{{ route('vaultix.destinations.destroy', $destination) }}" method="POST" onsubmit="return confirm('Are you sure? This will delete all backup history for this destination.')" class="hidden">
        @csrf @method('DELETE')
    </form>

    <!-- Full Provider Guides (Visible only when selected) -->
    <div id="guide-gdrive" class="provider-fields mt-12 bg-white rounded-2xl border shadow-sm overflow-hidden {{ $destination->provider == 'gdrive' ? '' : 'hidden' }}">
        <div class="p-6 border-b bg-slate-50">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Full Google Drive Setup Guide
            </h3>
        </div>
        <div class="p-8 text-sm text-slate-600 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-4">
                    <p class="font-bold text-slate-900 underline">Step 1: Google Cloud Console</p>
                    <ul class="list-disc list-inside space-y-2">
                        <li>Go to <a href="https://console.cloud.google.com/" target="_blank" class="text-indigo-600 font-bold underline">Google Cloud Console</a>.</li>
                        <li>Create a new project or select an existing one.</li>
                        <li>Search for <strong>"Google Drive API"</strong> and click <strong>Enable</strong>.</li>
                    </ul>
                </div>
                <div class="space-y-4">
                    <p class="font-bold text-slate-900 underline">Step 2: OAuth Consent Screen</p>
                    <ul class="list-disc list-inside space-y-2">
                        <li>Go to <strong>APIs & Services > OAuth consent screen</strong>.</li>
                        <li>Choose <strong>External</strong> and fill in the app name and email.</li>
                        <li>Add scopes: <code>.../auth/drive.file</code> if requested.</li>
                    </ul>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-4 border-t">
                <div class="space-y-4">
                    <p class="font-bold text-slate-900 underline">Step 3: Create Credentials</p>
                    <ul class="list-disc list-inside space-y-2">
                        <li>Go to <strong>APIs & Services > Credentials</strong>.</li>
                        <li>Click <strong>Create Credentials > OAuth client ID</strong>.</li>
                        <li>Select <strong>Web application</strong> as the type.</li>
                    </ul>
                </div>
                <div class="space-y-4">
                    <p class="font-bold text-slate-900 underline">Step 4: URIs Configuration</p>
                    <ul class="list-disc list-inside space-y-2">
                        <li><strong>Authorized JavaScript Origins</strong>: Paste only the domain (e.g., <code class="bg-slate-100 px-1 py-0.5 rounded">{{ url('/') }}</code>).</li>
                        <li><strong>Authorized Redirect URIs</strong>: Paste the full callback URL: <code class="bg-slate-100 px-1 py-0.5 rounded text-indigo-700">{{ route('vaultix.auth.google.callback') }}</code></li>
                        <li>Click <strong>Create</strong> and copy your Client ID & Secret!</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .selected-provider { border-color: #6366f1 !important; background-color: #f5f3ff !important; }
    .selected-provider span { color: #4f46e5 !important; }
</style>

<script>
    function calculateRetention() {
        const freqInput = document.querySelector('input[name="frequency"]:checked');
        const freq = freqInput ? freqInput.value : 'daily';
        const allDays = parseInt(document.querySelector('input[name="keep_all_backups_for_days"]').value) || 0;
        const dailyDays = parseInt(document.querySelector('input[name="keep_daily_backups_for_days"]').value) || 0;
        const weeklyWeeks = parseInt(document.querySelector('input[name="keep_weekly_backups_for_weeks"]').value) || 0;
        const monthlyMonths = parseInt(document.querySelector('input[name="keep_monthly_backups_for_months"]').value) || 0;

        const totalDays = Math.max(allDays, dailyDays, weeklyWeeks * 7, monthlyMonths * 30);
        let coverageText = "";
        if (totalDays >= 365) coverageText = `<b>${(totalDays/365).toFixed(1)} Year(s)</b>`;
        else if (totalDays >= 30) coverageText = `<b>${(totalDays/30).toFixed(1)} Month(s)</b>`;
        else coverageText = `<b>${totalDays} Day(s)</b>`;

        let backupsPerDay = 1;
        if (freq === 'hourly') backupsPerDay = 24;
        else if (freq === '6_hours') backupsPerDay = 4;
        else if (freq === '12_hours') backupsPerDay = 2;
        else if (freq === 'weekly') backupsPerDay = 1/7;
        else if (freq === 'monthly') backupsPerDay = 1/30;

        let estimatedFiles = 0;
        if (freq === 'monthly') {
            estimatedFiles = monthlyMonths || (allDays / 30) || 1;
        } else if (freq === 'weekly') {
            estimatedFiles = Math.max(weeklyWeeks, monthlyMonths);
        } else {
            estimatedFiles += allDays * backupsPerDay;
            if (dailyDays > allDays) estimatedFiles += (dailyDays - allDays);
            if (weeklyWeeks * 7 > dailyDays) estimatedFiles += (weeklyWeeks - (dailyDays / 7));
            if (monthlyMonths * 30 > weeklyWeeks * 7) estimatedFiles += (monthlyMonths - (weeklyWeeks * 7 / 30));
        }

        const totalFiles = Math.ceil(estimatedFiles);
        
        document.getElementById('retention-summary-text').innerHTML = 
            `Total Coverage: ${coverageText} | Estimated Storage: <b>~${totalFiles} files</b> in total.`;
    }

    const smartDefaults = {
        'hourly': { all: 1, daily: 7, weekly: 4, monthly: 3 },
        '6_hours': { all: 3, daily: 14, weekly: 8, monthly: 6 },
        '12_hours': { all: 5, daily: 21, weekly: 8, monthly: 6 },
        'daily': { all: 7, daily: 30, weekly: 8, monthly: 12 },
        'weekly': { all: 30, daily: 60, weekly: 26, monthly: 24 },
        'monthly': { all: 60, daily: 90, weekly: 52, monthly: 48 }
    };

    function updateSelection(input, className) {
        document.querySelectorAll('.' + className).forEach(el => {
            el.classList.remove('selected-provider', 'border-indigo-500', 'bg-indigo-50');
            if (el.querySelector('span')) {
                el.querySelector('span').classList.remove('text-indigo-600');
                el.querySelector('span').classList.add('text-slate-600');
            }
        });
        
        const parent = input.closest('.' + className);
        parent.classList.add('selected-provider', 'border-indigo-500', 'bg-indigo-50');
        if (parent.querySelector('span')) parent.querySelector('span').classList.add('text-indigo-600');

        if (className === 'provider-label') {
            document.querySelectorAll('.provider-fields').forEach(el => el.classList.add('hidden'));
            const fieldsEl = document.getElementById('fields-' + input.value);
            if (fieldsEl) fieldsEl.classList.remove('hidden');
        }

        if (className === 'freq-label') {
            const defaults = smartDefaults[input.value];
            if (defaults) {
                document.querySelector('input[name="keep_all_backups_for_days"]').value = defaults.all;
                document.querySelector('input[name="keep_daily_backups_for_days"]').value = defaults.daily;
                document.querySelector('input[name="keep_weekly_backups_for_weeks"]').value = defaults.weekly;
                document.querySelector('input[name="keep_monthly_backups_for_months"]').value = defaults.monthly;
            }

            const schedDetails = document.getElementById('scheduling-details');
            const weeklyDay = document.getElementById('weekly-day-selector');
            const monthlyDay = document.getElementById('monthly-day-selector');
            
            if (input.value === 'hourly') {
                schedDetails.classList.add('hidden');
            } else {
                schedDetails.classList.remove('hidden');
                weeklyDay.classList.toggle('hidden', input.value !== 'weekly');
                monthlyDay.classList.toggle('hidden', input.value !== 'monthly');
            }
            calculateRetention();
        }
    }

    function generateGoogleToken() {
        const clientId = document.getElementById('gdrive_client_id').value;
        const clientSecret = document.getElementById('gdrive_client_secret').value;
        
        if (!clientId || !clientSecret) {
            alert('Please enter Client ID and Client Secret first.');
            return;
        }

        // Pass destination_id so the callback redirects back to THIS edit page
        window.location.href = `{{ route('vaultix.auth.google.redirect') }}?client_id=${clientId}&client_secret=${clientSecret}&destination_id={{ $destination->id }}`;
    }

    // Initialize selection colors on load
    window.onload = () => {
        document.querySelectorAll('input:checked').forEach(input => {
            const labelClass = [...input.parentElement.classList].find(c => c.includes('-label'));
            if (labelClass) updateSelection(input, labelClass);
        });
        calculateRetention();
    };
</script>
@endsection
