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

        .title { text-align: center; font-size: 13pt; font-weight: bold; color: {{ $primaryColor }}; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 1px; }

        /* Table */
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .info-table td { padding: 4px 6px; vertical-align: top; border: 1px solid #ccc; font-size: 10pt; }
        .info-table .label { background-color: #f0f0f0; font-weight: bold; width: 30%; }
        .info-table .value { width: 70%; }

        /* Employee table */
        .employee-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .employee-table th { background-color: {{ $primaryColor }}; color: #fff; padding: 4px 6px; font-size: 9pt; text-align: left; border: 1px solid {{ $primaryColor }}; }
        .employee-table td { padding: 4px 6px; border: 1px solid #ccc; font-size: 9pt; }

        /* Signatures */
        .signatures { width: 100%; margin-top: 25px; }
        .sig-block { width: 45%; display: inline-block; text-align: center; }
        .sig-line { border-top: 1px solid #000; margin-top: 40px; padding-top: 4px; font-size: 9pt; }

        /* Footer */
        .footer { margin-top: 15px; text-align: center; font-size: 8pt; color: #999; border-top: 1px solid #ddd; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="page">
        {{-- Header --}}
        <div class="header">
            <h1>{{ $companyName }}</h1>
            <h2>{{ $companyAddress }}</h2>
        </div>

        <div class="title">Official Business Certificate</div>

        {{-- Certificate Info --}}
        <table class="info-table">
            <tr>
                <td class="label">Certificate Type</td>
                <td class="value">{{ $certificate->type?->label() }}</td>
                <td class="label">Status</td>
                <td class="value">{{ $certificate->status?->label() }}</td>
            </tr>
            <tr>
                <td class="label">Office Name</td>
                <td class="value" colspan="3">{{ $certificate->office_name }}</td>
            </tr>
            <tr>
                <td class="label">Time From</td>
                <td class="value">{{ $certificate->time_from }}</td>
                <td class="label">Time To</td>
                <td class="value">{{ $certificate->time_to }}</td>
            </tr>
        </table>

        {{-- Representative Info --}}
        @if($certificate->representative_name)
        <table class="info-table">
            <tr>
                <td class="label">Representative Name</td>
                <td class="value">{{ $certificate->representative_name }}</td>
                <td class="label">Position</td>
                <td class="value">{{ $certificate->representative_position ?? 'N/A' }}</td>
            </tr>
            @if($certificate->representative_contact)
            <tr>
                <td class="label">Contact</td>
                <td class="value" colspan="3">{{ $certificate->representative_contact }}</td>
            </tr>
            @endif
        </table>
        @endif

        {{-- Pass Slip Info --}}
        @if($certificate->passSlip)
        <table class="info-table">
            <tr>
                <td class="label">Pass Slip #</td>
                <td class="value">{{ $certificate->passSlip->slip_number }}</td>
                <td class="label">Date</td>
                <td class="value">{{ $certificate->passSlip->date?->format('M d, Y') }}</td>
            </tr>
            <tr>
                <td class="label">Purpose</td>
                <td class="value" colspan="3">{{ $certificate->passSlip->purpose }}</td>
            </tr>
        </table>

        {{-- Employees --}}
        @if($certificate->passSlip->employees->count())
        <table class="employee-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Employee Name</th>
                    <th>Employee #</th>
                </tr>
            </thead>
            <tbody>
                @foreach($certificate->passSlip->employees as $index => $employee)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $employee->full_name }}</td>
                    <td>{{ $employee->employee_number }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
        @endif

        {{-- Signatures --}}
        <table class="signatures">
            <tr>
                <td class="sig-block">
                    <div class="sig-line">
                        <strong>Submitted By</strong><br>
                        {{ $certificate->submittedBy?->name ?? 'N/A' }}
                    </div>
                </td>
                <td class="sig-block">
                    <div class="sig-line">
                        <strong>Verified By</strong><br>
                        {{ $certificate->verifiedBy?->name ?? 'N/A' }}
                    </div>
                </td>
            </tr>
        </table>

        {{-- Footer --}}
        <div class="footer">
            Generated on {{ now()->format('M d, Y h:i A') }} | {{ $companyName }}
        </div>
    </div>
</body>
</html>
