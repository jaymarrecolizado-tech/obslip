<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pass Slip Verification — {{ $passSlip?->slip_number ?? 'Not Found' }}</title>
    <style>
        :root { --primary: #1e3a5f; --muted: #6b7280; --bg: #f3f4f6; --card: #ffffff; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: var(--bg);
            color: #111827;
            display: flex;
            justify-content: center;
            padding: 24px 16px;
        }
        .wrap { width: 100%; max-width: 520px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { font-size: 18px; margin: 0; color: var(--primary); letter-spacing: .3px; }
        .header p { margin: 4px 0 0; font-size: 13px; color: var(--muted); }
        .card {
            background: var(--card);
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
        }
        .verified-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 16px;
        }
        .row { display: flex; justify-content: space-between; gap: 16px; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
        .row:last-child { border-bottom: none; }
        .label { color: var(--muted); font-size: 13px; min-width: 130px; }
        .value { font-weight: 600; text-align: right; font-size: 14px; word-break: break-word; }
        .status {
            display: inline-block; padding: 3px 10px; border-radius: 999px;
            font-size: 12px; font-weight: 700; text-transform: capitalize;
        }
        .status-success { background: #ecfdf5; color: #047857; }
        .status-info    { background: #eff6ff; color: #1d4ed8; }
        .status-warning { background: #fffbeb; color: #b45309; }
        .status-danger  { background: #fef2f2; color: #b91c1c; }
        .status-gray    { background: #f3f4f6; color: #374151; }
        .status-primary { background: #eff6ff; color: #1e40af; }
        .notfound { text-align: center; padding: 12px 0; }
        .notfound .icon { font-size: 40px; }
        .footer { text-align: center; color: var(--muted); font-size: 12px; margin-top: 18px; line-height: 1.5; }
        .lock { font-size: 12px; color: var(--muted); text-align: center; margin-top: 12px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="header">
            <h1>DICT Region II</h1>
            <p>Official Business Pass Slip — Verification</p>
        </div>

        @if (! $passSlip)
            <div class="card notfound">
                <div class="icon">⚠️</div>
                <h2 style="margin: 8px 0 4px; font-size: 18px;">Pass slip not found</h2>
                <p style="margin: 0; color: var(--muted); font-size: 14px;">
                    The QR code could not be matched to a valid pass slip.
                </p>
            </div>
        @else
            <div class="card">
                <span class="verified-badge">✓ Verified Record</span>

                <div class="row">
                    <span class="label">Slip Number</span>
                    <span class="value">{{ $passSlip->slip_number }}</span>
                </div>

                <div class="row">
                    <span class="label">Employee</span>
                    <span class="value">{{ $passSlip->employees->pluck('full_name')->implode(', ') ?: '—' }}</span>
                </div>

                <div class="row">
                    <span class="label">Date</span>
                    <span class="value">{{ $passSlip->date?->format('M d, Y') ?? '—' }}</span>
                </div>

                <div class="row">
                    <span class="label">Status</span>
                    <span class="value">
                        <span class="status status-{{ $passSlip->status?->color() ?? 'gray' }}">
                            {{ $passSlip->status?->label() ?? 'Unknown' }}
                        </span>
                    </span>
                </div>

                <div class="row">
                    <span class="label">Purpose</span>
                    <span class="value">{{ $passSlip->purpose ?? '—' }}</span>
                </div>

                <div class="row">
                    <span class="label">Duration</span>
                    <span class="value">
                        @if ($passSlip->duration_hours)
                            {{ number_format((float) $passSlip->duration_hours, 2) }} hrs
                        @else
                            —
                        @endif
                    </span>
                </div>
            </div>
        @endif

        <p class="lock">🔒 Read-only verification. No login required.</p>
        <p class="footer">
            This page confirms the existence and status of an Official Business Pass Slip.<br>
            For discrepancies, please contact the DICT Region II Administrative Office.
        </p>
    </div>
</body>
</html>
