<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate - {{ $certificate->passSlip->slip_number }}</title>
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
            border-bottom: 3px double #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 14pt;
            margin: 0 0 5px 0;
        }

        .header h2 {
            font-size: 11pt;
            color: #666;
            margin: 0;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }

        .field {
            margin-bottom: 8px;
        }

        .label {
            font-weight: bold;
        }

        .value {
            border-bottom: 1px solid #ccc;
            padding-bottom: 2px;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .signature-section {
            margin-top: 30px;
            border-top: 1px solid #000;
            padding-top: 20px;
        }

        .signature-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 40px;
            padding: 0 20px;
        }

        .signature-item {
            display: flex;
            flex-direction: column;
        }

        .signature-item input {
            border: none;
            border-bottom: 1px solid #000;
            outline: none;
            width: 200px;
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
        }

        @media print {
            .page {
                padding: 10mm;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <h1>CERTIFICATE OF ATTENDANCE</h1>
            <h2>{{ $certificate->passSlip->date->format('F j, Y') }}</h2>
        </div>

        <div class="grid">
            <div class="field">
                <div class="label">Pass Slip Number:</div>
                <div class="value">{{ $certificate->passSlip->slip_number }}</div>
            </div>
            <div class="field">
                <div class="label">Certificate Type:</div>
                <div class="value">{{ $certificate->type->getLabel() }}</div>
            </div>
        </div>

        <div class="grid">
            <div class="field">
                <div class="label">Employee Name:</div>
                <div class="value">{{ $certificate->passSlip->employee->full_name }}</div>
            </div>
            <div class="field">
                <div class="label">Employee Number:</div>
                <div class="value">{{ $certificate->passSlip->employee->employee_number }}</div>
            </div>
        </div>

        <div class="grid">
            <div class="field">
                <div class="label">Department:</div>
                <div class="value">{{ $certificate->passSlip->department->name }}</div>
            </div>
            <div class="field">
                <div class="label">Position:</div>
                <div class="value">{{ $certificate->passSlip->employee->position }}</div>
            </div>
        </div>

        <div class="section full-width">
            <div class="section-title">Official Business Details</div>
            <div class="field">
                <div class="label">Purpose:</div>
                <div class="value full-width">{{ $certificate->passSlip->purpose }}</div>
            </div>
            <div class="field">
                <div class="label">Office Name:</div>
                <div class="value">{{ $certificate->office_name }}</div>
            </div>
            <div class="field">
                <div class="label">Date:</div>
                <div class="value">{{ $certificate->passSlip->date->format('F j, Y') }}</div>
            </div>
            <div class="field">
                <div class="label">Time From:</div>
                <div class="value">{{ $certificate->time_from }}</div>
            </div>
            <div class="field">
                <div class="label">Time To:</div>
                <div class="value">{{ $certificate->time_to }}</div>
            </div>
        </div>

        <div class="section full-width">
            <div class="section-title">Representative Details</div>
            <div class="field">
                <div class="label">Name:</div>
                <div class="value">{{ $certificate->representative_name }}</div>
            </div>
            <div class="field">
                <div class="label">Position:</div>
                <div class="value">{{ $certificate->representative_position }}</div>
            </div>
            <div class="field">
                <div class="label">Contact:</div>
                <div class="value">{{ $certificate->representative_contact ?? 'N/A' }}</div>
            </div>
        </div>

        <div class="signature-section">
            <h3>Certification</h3>
            <p style="margin: 20px 0;">This is to certify that <strong>{{ $certificate->passSlip->employee->full_name }}</strong> attended official business at <strong>{{ $certificate->office_name }}</strong> on the date specified above.</p>

            <div class="signature-line">
                <div class="signature-item">
                    <div class="label">Representative Signature:</div>
                    <input type="text" placeholder="Sign here" />
                </div>
                <div class="signature-item">
                    <div class="label">Date:</div>
                    <div class="value">{{ now()->format('F j, Y') }}</div>
                </div>
            </div>

            <div class="signature-line">
                <div class="signature-item">
                    <div class="label">Verified By:</div>
                    <div class="value">{{ $certificate->verifiedBy->name ?? 'Pending Verification' }}</div>
                </div>
                <div class="signature-item">
                    <div class="label">Verified Date:</div>
                    <div class="value">{{ $certificate->verified_at?->format('F j, Y') ?? 'Pending' }}</div>
                </div>
            </div>
        </div>

        <div style="margin-top: 30px; text-align: center; font-size: 9pt; color: #666;">
            Generated by DICT Region II Official Business Pass Slip System
        </div>
    </div>
</body>
</html>