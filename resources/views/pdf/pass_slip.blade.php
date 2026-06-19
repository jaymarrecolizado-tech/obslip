<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Business Pass Slip - {{ $passSlip->slip_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.4;
            background: white;
            color: #000;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 20mm;
            margin: 0 auto;
            background: white;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .header h1 {
            font-size: 16pt;
            text-transform: uppercase;
            font-weight: bold;
            margin: 0;
        }

        .header h2 {
            font-size: 10pt;
            margin: 5px 0 0 0;
            color: #666;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 15px;
        }

        .field {
            display: flex;
            margin-bottom: 8px;
        }

        .label {
            font-weight: bold;
            min-width: 120px;
            flex-shrink: 0;
        }

        .value {
            flex: 1;
            border-bottom: 1px solid #000;
            padding-bottom: 1px;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .section {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #000;
        }

        .section-title {
            background: #000;
            color: #fff;
            text-align: center;
            padding: 5px;
            font-weight: bold;
            text-transform: uppercase;
            margin: -10px -10px 10px -10px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        .table th, .table td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }

        .table th {
            background: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            border-top: 2px solid #000;
            padding-top: 10px;
            font-size: 9pt;
        }

        .emergency {
            color: #d32f2f !important;
            font-weight: bold !important;
        }

        @media print {
            .page {
                padding: 10mm;
            }

            .qr-code-container {
                display: block !important;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <h1>Official Business Pass Slip</h1>
            <h2>Department of Information and Communications Technology - Region II</h2>
        </div>

        <div class="grid">
            <div class="field">
                <div class="label">Slip Number:</div>
                <div class="value">{{ $passSlip->slip_number }}</div>
            </div>
            <div class="field">
                <div class="label">Date:</div>
                <div class="value">{{ $passSlip->date->format('F j, Y') }}</div>
            </div>
        </div>

        <div class="grid">
            <div class="field">
                <div class="label">Employee Name:</div>
                <div class="value">{{ $passSlip->employee->full_name }}</div>
            </div>
            <div class="field">
                <div class="label">Employee Number:</div>
                <div class="value">{{ $passSlip->employee->employee_number }}</div>
            </div>
        </div>

        <div class="grid">
            <div class="field">
                <div class="label">Department:</div>
                <div class="value">{{ $passSlip->department->name }}</div>
            </div>
            <div class="field">
                <div class="label">Position:</div>
                <div class="value">{{ $passSlip->employee->position }}</div>
            </div>
        </div>

        <div class="grid">
            <div class="field">
                <div class="label">Purpose:</div>
                <div class="value full-width">{{ $passSlip->purpose }}</div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Transportation Details</div>
            <div class="grid">
                <div class="field">
                    <div class="label">Transport Type:</div>
                    <div class="value">{{ $passSlip->transport_type->getLabel() }}</div>
                </div>
                @if($passSlip->vehicle)
                <div class="field">
                    <div class="label">Vehicle:</div>
                    <div class="value">{{ $passSlip->vehicle->full_name }}</div>
                </div>
                @endif
            </div>
        </div>

        <div class="section">
            <div class="section-title">Approval Details</div>
            <table class="table">
                <tr>
                    <th>Supervisor</th>
                    <th>Date Approved</th>
                </tr>
                <tr>
                    <td>{{ $passSlip->supervisor->name ?? 'N/A' }}</td>
                    <td>{{ $passSlip->approved_at?->format('F j, Y g:i A') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Created By</th>
                    <th>Created At</th>
                </tr>
                <tr>
                    <td>{{ $passSlip->creator->name }}</td>
                    <td>{{ $passSlip->created_at->format('F j, Y g:i A') }}</td>
                </tr>
            </table>
        </div>

        @if($passSlip->is_emergency)
        <div class="section">
            <div class="section-title emergency">⚠ EMERGENCY PASS SLIP</div>
        </div>
        @endif

        <div class="section">
            <div class="section-title">Travel Log</div>
            <table class="table">
                <tr>
                    <th>Action</th>
                    <th>Time</th>
                </tr>
                <tr>
                    <td>Departure</td>
                    <td>{{ $passSlip->departure_time?->format('F j, Y g:i A') ?? '--:--' }}</td>
                </tr>
                <tr>
                    <td>Arrival</td>
                    <td>{{ $passSlip->arrival_time?->format('F j, Y g:i A') ?? '--:--' }}</td>
                </tr>
                @if($passSlip->duration_hours)
                <tr>
                    <td>Duration</td>
                    <td>{{ number_format($passSlipduration_hours, 2) }} hours</td>
                </tr>
                @endif
            </table>
        </div>

        <div class="footer">
            <p><strong>Remarks:</strong> This slip serves as an official document for the duration specified above.</p>
            <p>For any inquiries, contact the HR Department at hr@dictr2.cloud or (078) 123-4567.</p>
            <p>Generated by DICT Region II Official Business Pass Slip System on {{ now()->format('F j, Y g:i A') }}</p>
        </div>
    </div>
</body>
</html>