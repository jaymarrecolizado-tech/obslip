<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: serif; font-size: 11pt; color: #000; }
        .page { width: 100%; padding: 20px; }

        /* Header */
        .header { text-align: center; border-bottom: 2px solid {{ $primaryColor }}; padding-bottom: 10px; margin-bottom: 15px; }
        .header h1 { font-size: 14pt; color: {{ $primaryColor }}; margin-bottom: 2px; }
        .header h2 { font-size: 11pt; font-weight: normal; color: #333; }
        .header .tagline { font-size: 9pt; color: #666; margin-top: 4px; }

        /* Copy label */
        .copy-label { text-align: center; font-size: 10pt; font-weight: bold; color: {{ $primaryColor }}; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 2px; }

        /* Table */
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .info-table td { padding: 4px 6px; vertical-align: top; border: 1px solid #ccc; font-size: 10pt; }
        .info-table .label { background-color: #f0f0f0; font-weight: bold; width: 30%; }
        .info-table .value { width: 70%; }

        /* Employee table */
        .employee-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .employee-table th { background-color: {{ $primaryColor }}; color: #fff; padding: 4px 6px; font-size: 9pt; text-align: left; border: 1px solid {{ $primaryColor }}; }
        .employee-table td { padding: 4px 6px; border: 1px solid #ccc; font-size: 9pt; }
        .employee-table tr:nth-child(even) { background-color: #f9f9f9; }

        /* Signatures */
        .signatures { width: 100%; margin-top: 20px; }
        .sig-row { display: flex; justify-content: space-between; }
        .sig-block { width: 30%; text-align: center; }
        .sig-line { border-top: 1px solid #000; margin-top: 40px; padding-top: 4px; font-size: 9pt; }

        /* Footer */
        .footer { margin-top: 15px; text-align: center; font-size: 8pt; color: #999; border-top: 1px solid #ddd; padding-top: 5px; }

        /* QR */
        .qr-section { text-align: right; margin-top: 10px; }
        .qr-section img { width: 80px; height: 80px; }
    </style>
</head>
<body>
    @foreach($copies as $copyLabel)
    <div class="page" @if(! $loop->last) style="page-break-after: always;" @endif>
        {{-- Header --}}
        <div class="header">
            <h1>{{ $companyName }}</h1>
            <h2>{{ $companyAddress }}</h2>
            <div class="tagline">{{ $tagline }}</div>
        </div>

        {{-- Copy Label --}}
        <div class="copy-label">{{ $copyLabel }}</div>

        {{-- Slip Info --}}
        <table class="info-table">
            <tr>
                <td class="label">Slip Number</td>
                <td class="value">{{ $passSlip->slip_number }}</td>
                <td class="label">Date</td>
                <td class="value">{{ $passSlip->date?->format('M d, Y') }}</td>
            </tr>
            <tr>
                <td class="label">Department</td>
                <td class="value">{{ $passSlip->department?->name ?? 'N/A' }}</td>
                <td class="label">Status</td>
                <td class="value">{{ $passSlip->status?->label() }}</td>
            </tr>
            <tr>
                <td class="label">Purpose</td>
                <td class="value" colspan="3">{{ $passSlip->purpose }}</td>
            </tr>
            <tr>
                <td class="label">Transport Type</td>
                <td class="value">{{ $passSlip->transport_type?->label() ?? 'N/A' }}</td>
                <td class="label">Vehicle</td>
                <td class="value">{{ $passSlip->vehicle?->name ?? 'N/A' }}</td>
            </tr>
            @if($passSlip->departure_time)
            <tr>
                <td class="label">Departure Time</td>
                <td class="value">{{ $passSlip->departure_time?->format('M d, Y h:i A') }}</td>
                <td class="label">Arrival Time</td>
                <td class="value">{{ $passSlip->arrival_time?->format('M d, Y h:i A') ?? 'Pending' }}</td>
            </tr>
            @endif
            @if($passSlip->duration_hours)
            <tr>
                <td class="label">Duration</td>
                <td class="value" colspan="3">{{ number_format($passSlip->duration_hours, 2) }} hours</td>
            </tr>
            @endif
            @if($passSlip->is_emergency)
            <tr>
                <td class="label">Type</td>
                <td class="value" colspan="3" style="color: red; font-weight: bold;">EMERGENCY</td>
            </tr>
            @endif
        </table>

        {{-- Employees --}}
        <table class="employee-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Employee Name</th>
                    <th>Employee #</th>
                    <th>Position</th>
                </tr>
            </thead>
            <tbody>
                @foreach($passSlip->employees as $index => $employee)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $employee->full_name }}</td>
                    <td>{{ $employee->employee_number }}</td>
                    <td>{{ $employee->position ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Signatures --}}
        <table class="signatures">
            <tr>
                <td class="sig-block">
                    <div class="sig-line">
                        <strong>Created By</strong><br>
                        {{ $passSlip->creator?->name ?? 'N/A' }}
                    </div>
                </td>
                <td class="sig-block">
                    <div class="sig-line">
                        <strong>Supervisor</strong><br>
                        {{ $passSlip->supervisor?->name ?? 'N/A' }}
                    </div>
                </td>
                <td class="sig-block">
                    <div class="sig-line">
                        <strong>Approved By</strong><br>
                        {{ $passSlip->approver?->name ?? 'N/A' }}
                    </div>
                </td>
            </tr>
        </table>

        {{-- QR Code --}}
        @if($showQr && $passSlip->qr_code)
        <div class="qr-section">
            <img src="{{ $qrCodeImage }}" alt="QR Code">
            <div style="font-size: 7pt; color: #999;">Scan to verify</div>
        </div>
        @endif

        {{-- Footer --}}
        <div class="footer">
            Generated on {{ now()->format('M d, Y h:i A') }} | {{ $companyName }}
        </div>
    </div>
    @endforeach
</body>
</html>
