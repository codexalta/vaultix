<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Inter', Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 0; }
        .wrapper { padding: 40px 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e2e8f0; }
        .header { padding: 32px; text-align: center; }
        .header.success { background-color: #10b981; }
        .header.failed { background-color: #f43f5e; }
        .header h1 { color: #ffffff; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.025em; }
        .content { padding: 32px; }
        .status-badge { display: inline-block; padding: 6px 12px; border-radius: 9999px; font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 20px; }
        .success .status-badge { background-color: #ecfdf5; color: #065f46; }
        .failed .status-badge { background-color: #fff1f2; color: #9f1239; }
        .details-table { width: 100%; border-collapse: collapse; margin-bottom: 32px; }
        .details-table td { padding: 12px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .details-table td.label { color: #64748b; font-weight: 500; width: 140px; }
        .details-table td.value { color: #1e293b; font-weight: 600; text-align: right; }
        .btn { display: block; text-align: center; background-color: #4f46e5; color: #ffffff; padding: 14px 24px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 14px; }
        .footer { padding: 24px; text-align: center; color: #94a3b8; font-size: 12px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container {{ $status }}">
            <div class="header {{ $status }}">
                <h1>Vaultix Backup Engine</h1>
            </div>
            <div class="content">
                <div class="status-badge">
                    @if($status === 'success') ✅ Success @else ❌ Failed @endif
                </div>
                <h2 style="color: #0f172a; margin-top: 0;">Backup {{ ucfirst($status) }}</h2>
                <p style="color: #475569; font-size: 14px; line-height: 24px;">
                    {{ $messageText ?? 'The scheduled backup job for your project has been completed. Below are the details of the operation.' }}
                </p>
                
                <table class="details-table">
                    <tr>
                        <td class="label">Job Name</td>
                        <td class="value">{{ $job->name }}</td>
                    </tr>
                    <tr>
                        <td class="label">Status</td>
                        <td class="value" style="color: {{ $status === 'success' ? '#10b981' : '#f43f5e' }}">{{ strtoupper($status) }}</td>
                    </tr>
                    @if($size)
                    <tr>
                        <td class="label">Backup Size</td>
                        <td class="value">{{ $size }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="label">Provider</td>
                        <td class="value">{{ $job->destination->provider }}</td>
                    </tr>
                    @if($error)
                    <tr>
                        <td class="label">Error Detail</td>
                        <td class="value" style="color: #f43f5e; font-size: 12px;">{{ $error }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="label">Completed At</td>
                        <td class="value">{{ now()->format('M d, Y H:i:s') }}</td>
                    </tr>
                </table>

                <a href="{{ $dashboardUrl }}" style="display: block; text-align: center; background-color: #4f46e5; color: #ffffff !important; padding: 16px 24px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 14px; margin-top: 20px;">View Dashboard</a>
            </div>
            <div class="footer">
                &copy; {{ date('Y') }} Vaultix Security. This is an automated security notification.
            </div>
        </div>
    </div>
</body>
</html>
