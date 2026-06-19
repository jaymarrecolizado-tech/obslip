<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pass Slip Verification - {{ $passSlip->slip_number }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 90%;
            overflow: hidden;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .status-approved { background: #d1fae5; color: #065f46; }
        .status-departed { background: #fff7ed; color: #b45309; }
        .status-arrived { background: #dbeafe; color: #047857; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-emergency { background: #fee2e2; color: #dc2626; }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #6b7280;
            font-weight: 500;
            font-size: 14px;
        }

        .info-value {
            font-weight: 600;
            color: #111827;
            text-align: right;
        }

        .pass-slip-number {
            font-family: 'Courier New', monospace;
            font-size: 18px;
            color: #667eea;
            text-align: center;
            padding: 15px;
            background: #f8fafc;
            border-radius: 8px;
            margin-bottom: 20px;
            letter-spacing: 1px;
        }

        .purpose {
            font-size: 16px;
            color: #374151;
            text-align: center;
            padding: 15px;
            background: #f0fdf4;
            border-radius: 8px;
            margin-bottom: 20px;
            font-style: italic;
        }

        .timestamp {
            text-align: center;
            color: #6b7280;
            font-size: 12px;
            margin-top: 15px;
        }

        @media (max-width: 480px) {
            .card {
                margin: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="card">
        @if($passSlip->is_emergency)
        <div class="status-badge status-emergency mb-3">
            <span>⚠</span> Emergency Pass Slip
        </div>
        @else
        <div class="status-badge status-{{ $passSlip->status }} mb-3">
            <span></span>
            @if($passSlip->status === 'approved') Check ✓
            @elseif($passSlip->status === 'departed') Car 🚗
            @elseif($passSlip->->status === 'arrived) Location 📍
            @elseif($passSlip->status === 'completed') Check ✓✓
            @endif
            {{ $passSlip->status_label }}
        </div>
        @endif

        <div class="pass-slip-number">
            {{ $passSlip->slip_number }}
        </div>

        <div class="purpose">
            "{{ $passSlip->purpose }}"
        </div>

        <div class="info-row">
            <span class="info-label">Employee</span>
            <span class="info-value">{{ $passSlip->employee->full_name }}</span>
        </div>

        <div class="info-row">
            <span class="info-label">Department</span>
            <span class="info-value">{{ $passSlip->department->name }}</span>
        </div>

        <div class="info-row">
            <span class="info-label">Date</span>
            <span class="info-value">{{ $passSlip->date->format('F j, Y') }}</span>
        </div>

        @if($passSlip->departure_time || $passSlip->arrival_time)
        <div class="info-row">
            <span class="info-label">
                @if($passSlip->departure_time)
                    Departed: {{ $passSlip->departure_time->format('g:i A') }}
                @else
                    Departed: --
                @endif
            </span>
            <span class="info-value">
                @if($passSlip->arrival_time)
                    Arrived: {{ $passSlip->arrival_time->format('g:i A') }}
                @else
                    Arrived: --
                @endif
            </span>
        </div>
        @endif

        @if($passSlip->duration_hours)
        <div class="info-row">
            <span class="info-label">Duration</span>
            <span class="info-value">{{ number_format($passSlip->duration_hours, 2) }} hrs</span>
        </div>
        @endif

        <div class="timestamp">
            Verified: {{ now()->format('M j, Y g:i A') }}
        </div>
    </div>
</body>
</html>