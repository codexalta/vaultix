<?php

namespace Codexalta\Vaultix\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Codexalta\Vaultix\Models\VaultixSetting;

class VaultixAuthorization
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        
        if (!$user) {
            abort(403, 'Unauthorized. Please login first.');
        }

        // 1. Check if Super Admin (via .env)
        $superAdmin = config('vaultix.super_admin');
        if ($superAdmin && $user->email === $superAdmin) {
            return $next($request);
        }

        // 2. Check if Email is in Authorized List (DB Settings)
        $authorizedEmails = VaultixSetting::get('authorized_emails', []);
        
        if (in_array($user->email, $authorizedEmails)) {
            return $next($request);
        }

        abort(403, 'You are not authorized to access Vaultix.');
    }
}
