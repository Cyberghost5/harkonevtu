<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Vouchers</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #fff;
            color: #000;
            margin: 0;
            padding: 20px;
        }

        @page {
            size: A4 portrait;
            margin: 5mm;
        }

        .voucher-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 2.5mm;
            max-width: 100%;
            margin: 0 auto;
        }

        .voucher-card {
            border: 1px dashed #64748b;
            border-radius: 4px;
            padding: 4px 5px;
            background: #fff;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            min-height: 125px;
            page-break-inside: avoid;
        }

        .network-header {
            font-size: 8px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
            border-bottom: 1.5px solid;
            padding-bottom: 1px;
            display: flex;
            justify-content: space-between;
        }

        /* Network Color Accents */
        .network-mtn { border-color: #facc15; color: #a16207; }
        .network-airtel { border-color: #ef4444; color: #b91c1c; }
        .network-glo { border-color: #22c55e; color: #15803d; }
        .network-9mobile { border-color: #0f766e; color: #0f766e; }

        .value-badge {
            font-size: 10px;
            font-weight: 800;
            margin: 2px 0;
        }

        .pin-section {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 3px 2px;
            text-align: center;
            margin: 3px 0;
        }

        .pin-label {
            font-size: 6px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1px;
        }

        .pin-code {
            font-family: 'Courier New', Courier, monospace;
            font-size: 9.5px;
            font-weight: 800;
            letter-spacing: 1px;
            color: #0f172a;
        }

        .serial-section {
            font-size: 6.5px;
            color: #475569;
            margin-bottom: 2px;
        }

        .instructions {
            font-size: 6px;
            color: #334155;
            line-height: 1.2;
            margin-top: 2px;
            border-top: 0.5px solid #e2e8f0;
            padding-top: 2px;
            font-weight: 600;
        }

        .business-footer {
            font-size: 5.5px;
            font-weight: bold;
            color: #94a3b8;
            text-align: center;
            margin-top: 2px;
            text-transform: uppercase;
        }

        /* Print styling rules */
        @media print {
            body {
                padding: 0;
                margin: 0;
                background: #fff;
            }
            .no-print {
                display: none !important;
            }
            .voucher-grid {
                grid-template-columns: repeat(5, 1fr) !important;
                gap: 2mm !important;
            }
            .voucher-card {
                border-style: dashed;
                border-color: #94a3b8;
            }
        }
        
        .print-btn-container {
            text-align: center;
            margin-bottom: 15px;
            display: flex;
            justify-content: center;
            gap: 12px;
        }
        .print-btn {
            background-color: #4f46e5;
            color: white;
            padding: 9px 20px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .print-btn.btn-secondary {
            background-color: #059669;
        }
        .print-btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>

    <div class="print-btn-container no-print">
        <button class="print-btn" onclick="window.print()">
            <svg style="width:16px;height:16px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Send to Printer (A4)
        </button>
        <a href="{{ route('services.print-pins.download-pdf', ['ids' => $vouchers->pluck('id')->implode(',')]) }}" class="print-btn btn-secondary">
            <svg style="width:16px;height:16px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Download as PDF (5 Columns)
        </a>
    </div>

    <div class="voucher-grid">
        @foreach($vouchers as $v)
        @php
            $netKey = strtolower($v->network);
            $netClass = 'network-' . $netKey;
        @endphp
        <div class="voucher-card">
            <div>
                <div class="network-header {{ $netClass }}">
                    {{ $v->network }} {{ $v->type === 'data' ? 'Data' : 'Airtime' }}
                </div>
                <div class="value-badge">
                    ₦{{ number_format($v->value) }}
                </div>
            </div>

            <div class="pin-section">
                <div class="pin-label">Recharge PIN</div>
                <div class="pin-code">{{ chunk_split($v->pin, 4, ' ') }}</div>
            </div>

            <div>
                <div class="serial-section">
                    <strong>S/N:</strong> {{ $v->serial_number }}
                    <span style="float: right;"><strong>Date:</strong> {{ $v->created_at->format('d/m/y') }}</span>
                </div>

                <div class="instructions">
                    @if($v->type === 'data')
                        Dial network-specific data balance code to verify allocation.
                    @else
                        @if($netKey === 'mtn') Dial *555*PIN# to load @elseif($netKey === 'airtel') Dial *126*PIN# to load @elseif($netKey === 'glo') Dial *123*PIN# to load @else Dial *222*PIN# to load @endif
                    @endif
                </div>

                @if($v->name_on_card)
                <div class="business-footer">
                    Powered by: {{ $v->name_on_card }}
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <script>
        // Trigger print dialog automatically when loaded
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
